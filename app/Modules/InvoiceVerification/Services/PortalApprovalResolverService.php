<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\Division;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PortalApprovalResolverService
{
    /**
     * Resolve Kepala Departemen and Kepala Divisi for a PPA Non Contract transaction.
     *
     * @return array{kadep: User, kadiv: User}
     */
    public function resolveApprovers(Transaction $transaction): array
    {
        $owner = $transaction->owner;
        if (! $owner || blank($owner->email)) {
            throw ValidationException::withMessages([
                'approval' => sprintf('Owner transaksi #%s tidak memiliki email valid untuk approval.', $transaction->registration_number),
            ]);
        }

        $portalResult = $this->callPortalResolver($transaction, $owner);

        $kadep = $portalResult['kadep'];
        $kadiv = $portalResult['kadiv'];

        if (! $kadep) {
            throw ValidationException::withMessages([
                'approval' => sprintf('Approver Kepala Departemen tidak ditemukan untuk transaksi #%s.', $transaction->registration_number),
            ]);
        }

        if (! $kadiv) {
            throw ValidationException::withMessages([
                'approval' => sprintf('Approver Kepala Divisi tidak ditemukan untuk transaksi #%s.', $transaction->registration_number),
            ]);
        }

        return [
            'kadep' => $kadep,
            'kadiv' => $kadiv,
        ];
    }

    /**
     * @return array{kadep: ?User, kadiv: ?User}
     */
    protected function callPortalResolver(Transaction $transaction, User $owner): array
    {
        $baseUrl = (string) config('invoice_verification.approval_resolver.base_url');
        $endpoint = (string) config('invoice_verification.approval_resolver.endpoint', '/api/v1/approval/resolve');
        $clientCode = (string) config('invoice_verification.approval_resolver.client_code', 'invoice_collector');
        $secret = (string) config('invoice_verification.approval_resolver.shared_secret');
        $timeout = (int) config('invoice_verification.approval_resolver.timeout', 15);
        $verifySsl = (bool) config('invoice_verification.approval_resolver.verify_ssl', true);

        if ($secret === '' || $baseUrl === '') {
            throw ValidationException::withMessages([
                'approval' => 'Konfigurasi koneksi Portal belum lengkap. Approval tidak dapat dimulai.',
            ]);
        }

        $amount = $transaction->invoiceMetadata
            ? (float) $transaction->invoiceMetadata->invoice_value + (float) $transaction->invoiceMetadata->ppn_value
            : (float) ($transaction->contract_value ?? 0);

        $payload = [
            'module' => $clientCode,
            'request_type' => 'ppa_non_contract',
            'requester_email' => $owner->email,
            'amount' => $amount,
            'metadata' => [
                'transaction_id' => (string) $transaction->id,
                'registration_number' => (string) $transaction->registration_number,
            ],
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            Log::warning('Failed to encode approval resolver payload for transaction.', ['transaction_id' => $transaction->id]);

            throw ValidationException::withMessages(['approval' => 'Payload approval Portal gagal dibuat.']);
        }

        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);
        $url = rtrim($baseUrl, '/').'/'.ltrim($endpoint, '/');

        try {
            $client = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-App-Code' => $clientCode,
                'X-Timestamp' => $timestamp,
                'X-Signature' => $signature,
            ])->timeout($timeout);

            if (! $verifySsl) {
                $client = $client->withoutVerifying();
            }

            $response = $client->withBody($body, 'application/json')->post($url);
        } catch (\Throwable $exception) {
            Log::warning('Approval resolver HTTP request failed: '.$exception->getMessage(), [
                'transaction_id' => $transaction->id,
            ]);

            throw ValidationException::withMessages([
                'approval' => 'Portal approval tidak dapat dihubungi. Silakan coba lagi atau hubungi administrator.',
            ]);
        }

        if (! $response->successful()) {
            Log::warning('Approval resolver returned non-success response.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'transaction_id' => $transaction->id,
            ]);

            $message = $this->portalErrorMessage($response->json(), 'Portal menolak permintaan resolver approval.');
            throw ValidationException::withMessages(['approval' => $message]);
        }

        $data = $response->json('data') ?? [];

        return $this->extractApproversFromPortalData($data, $transaction);
    }

    /**
     * Resolve and synchronize the logged-in user's division and department from Portal.
     *
     * @return array{division: Division, department: Department}
     */
    public function syncRequesterOrganization(User $user): array
    {
        if (blank($user->email)) {
            throw ValidationException::withMessages(['organization' => 'Email akun login belum tersedia.']);
        }

        $data = $this->callResolverForUser($user);
        $divisionData = data_get($data, 'requester.organization.division');
        $departmentData = data_get($data, 'requester.organization.department');

        if (! is_array($divisionData) || blank($divisionData['code'] ?? null) || blank($divisionData['name'] ?? null)) {
            throw ValidationException::withMessages([
                'organization' => 'Divisi akun '.$user->email.' belum terdaftar pada struktur organisasi Portal.',
            ]);
        }

        if (! is_array($departmentData) || blank($departmentData['code'] ?? null) || blank($departmentData['name'] ?? null)) {
            throw ValidationException::withMessages([
                'organization' => 'Departemen akun '.$user->email.' belum terdaftar pada struktur organisasi Portal.',
            ]);
        }

        $division = Division::query()->updateOrCreate(
            ['ldap_code' => (string) $divisionData['code']],
            ['name' => (string) $divisionData['name'], 'is_active' => true, 'last_synced_at' => now()],
        );
        $department = Department::query()->updateOrCreate(
            ['ldap_code' => (string) $departmentData['code']],
            [
                'division_id' => $division->id,
                'name' => (string) $departmentData['name'],
                'is_active' => true,
                'last_synced_at' => now(),
            ],
        );

        $user->forceFill([
            'division_id' => $division->id,
            'department_id' => $department->id,
            'employee_number' => data_get($data, 'requester.employee_number') ?: $user->employee_number,
            'last_synced_at' => now(),
        ])->save();

        return compact('division', 'department');
    }

    /** @return array<string, mixed> */
    protected function callResolverForUser(User $user): array
    {
        $baseUrl = (string) config('invoice_verification.approval_resolver.base_url');
        $endpoint = (string) config('invoice_verification.approval_resolver.endpoint', '/api/v1/approval/resolve');
        $clientCode = (string) config('invoice_verification.approval_resolver.client_code', 'invoice_collector');
        $secret = (string) config('invoice_verification.approval_resolver.shared_secret');

        if ($baseUrl === '' || $secret === '') {
            throw ValidationException::withMessages(['organization' => 'Konfigurasi koneksi Portal belum lengkap.']);
        }

        $payload = [
            'module' => $clientCode,
            'request_type' => 'ppa_non_contract',
            'requester_email' => $user->email,
            'amount' => 0,
            'metadata' => ['purpose' => 'organization_sync'],
        ];

        return $this->signedPortalRequest($baseUrl, $endpoint, $clientCode, $secret, $payload, 'organization');
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    protected function signedPortalRequest(string $baseUrl, string $endpoint, string $clientCode, string $secret, array $payload, string $errorKey): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            throw ValidationException::withMessages([$errorKey => 'Payload Portal gagal dibuat.']);
        }

        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

        try {
            $client = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-App-Code' => $clientCode,
                'X-Timestamp' => $timestamp,
                'X-Signature' => $signature,
            ])->timeout((int) config('invoice_verification.approval_resolver.timeout', 15));

            if (! config('invoice_verification.approval_resolver.verify_ssl', true)) {
                $client = $client->withoutVerifying();
            }

            $response = $client->withBody($body, 'application/json')->post(rtrim($baseUrl, '/').'/'.ltrim($endpoint, '/'));
        } catch (\Throwable $exception) {
            Log::warning('Portal request failed: '.$exception->getMessage(), ['endpoint' => $endpoint]);
            throw ValidationException::withMessages([$errorKey => 'Portal tidak dapat dihubungi. Silakan coba lagi.']);
        }

        if (! $response->successful()) {
            $message = $this->portalErrorMessage($response->json(), 'Portal menolak permintaan aplikasi.');
            throw ValidationException::withMessages([$errorKey => $message]);
        }

        return (array) ($response->json('data') ?? []);
    }

    /** @param array<string, mixed>|null $payload */
    protected function portalErrorMessage(?array $payload, string $fallback): string
    {
        $errors = collect((array) data_get($payload, 'errors'))
            ->flatten()
            ->filter(fn ($message) => is_string($message) && $message !== '');

        return (string) ($errors->first() ?: data_get($payload, 'message') ?: $fallback);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{kadep: ?User, kadiv: ?User}
     */
    protected function extractApproversFromPortalData(array $data, Transaction $transaction): array
    {
        $kadep = null;
        $kadiv = null;

        // Check if steps array exists
        $steps = data_get($data, 'steps', []);
        if (is_array($steps) && ! empty($steps)) {
            foreach ($steps as $step) {
                $order = data_get($step, 'step_order') ?? data_get($step, 'step_no');
                $approverData = data_get($step, 'approver');
                $email = data_get($approverData, 'email') ?? data_get($step, 'email');

                if (! $email) {
                    continue;
                }

                if ($order == 1 && ! $kadep) {
                    $kadep = $this->findOrProvisionUser($email, $approverData, RoleCode::KEPALA_DEPARTEMEN, $transaction);
                } elseif ($order == 2 && ! $kadiv) {
                    $kadiv = $this->findOrProvisionUser($email, $approverData, RoleCode::KEPALA_DIVISI, $transaction);
                }
            }
        }

        // Direct property check if not resolved via steps
        if (! $kadep) {
            $kadepEmail = data_get($data, 'department_head.email') ?? data_get($data, 'kadep.email');
            if ($kadepEmail) {
                $kadep = $this->findOrProvisionUser($kadepEmail, data_get($data, 'department_head'), RoleCode::KEPALA_DEPARTEMEN, $transaction);
            }
        }

        if (! $kadiv) {
            $kadivEmail = data_get($data, 'division_head.email') ?? data_get($data, 'kadiv.email');
            if ($kadivEmail) {
                $kadiv = $this->findOrProvisionUser($kadivEmail, data_get($data, 'division_head'), RoleCode::KEPALA_DIVISI, $transaction);
            }
        }

        return [
            'kadep' => $kadep,
            'kadiv' => $kadiv,
        ];
    }

    protected function findOrProvisionUser(string $email, ?array $snapshot, RoleCode $role, Transaction $transaction): User
    {
        $user = User::query()->where('email', $email)->first();
        if ($user) {
            $user->forceFill([
                'name' => data_get($snapshot, 'name') ?: $user->name,
                'employee_number' => data_get($snapshot, 'employee_number') ?: $user->employee_number,
                'role_code' => $role,
                'division_id' => $transaction->division_id,
                'department_id' => $role === RoleCode::KEPALA_DEPARTEMEN ? $transaction->department_id : null,
                'is_active' => true,
                'last_synced_at' => now(),
            ])->save();

            return $user->refresh();
        }

        return User::query()->create([
            'name' => data_get($snapshot, 'name') ?? Str::headline(Str::before($email, '@')),
            'email' => $email,
            'employee_number' => data_get($snapshot, 'employee_number'),
            'role_code' => $role,
            'division_id' => $transaction->division_id,
            'department_id' => $role === RoleCode::KEPALA_DEPARTEMEN ? $transaction->department_id : null,
            'is_active' => true,
            'password' => Hash::make(Str::random(24)),
        ]);
    }

    /**
     * Request digital signature & QR code from Portal upon approval.
     *
     * @return array{portal_signature_id: ?string, portal_signature_url: ?string, portal_qr_url: ?string}|null
     */
    public function requestPortalSignature(
        \App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction $approval,
        User $actor,
        ?string $notes = null,
    ): ?array {
        $baseUrl = (string) config('invoice_verification.approval_resolver.base_url');
        $secret = (string) config('invoice_verification.approval_resolver.shared_secret');
        $timeout = (int) config('invoice_verification.approval_resolver.timeout', 15);
        $verifySsl = (bool) config('invoice_verification.approval_resolver.verify_ssl', true);

        if ($secret === '' || $baseUrl === '') {
            throw ValidationException::withMessages(['signature' => 'Konfigurasi QR signature Portal belum lengkap.']);
        }

        $transaction = $approval->transaction;
        $stepNo = (int) ($approval->approvalFlow?->step_no ?? 1);

        $payload = [
            'module' => (string) config('invoice_verification.approval_resolver.client_code', 'invoice_collector'),
            'signature_type' => 'approval_decision',
            'role' => $stepNo === 1 ? 'kadep_approver' : 'kadiv_approver',
            'ticket' => [
                'id' => (string) $transaction->id,
                'ticket_no' => (string) $transaction->registration_number,
                'title' => (string) $transaction->title,
                'request_type' => 'ppa_non_contract',
            ],
            'approval' => [
                'id' => (string) $approval->id,
                'level' => $stepNo,
                'status' => 'approved',
                'comment' => $notes,
            ],
            'signer' => [
                'id' => (string) $actor->id,
                'name' => (string) $actor->name,
                'email' => (string) $actor->email,
            ],
            'signed_at' => now()->toIso8601String(),
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            throw ValidationException::withMessages(['signature' => 'Payload QR signature Portal gagal dibuat.']);
        }

        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);
        $clientCode = (string) config('invoice_verification.approval_resolver.client_code', 'invoice_collector');
        $signatureEndpoint = (string) config('invoice_verification.approval_resolver.signature_endpoint', '/api/v1/signatures');
        $url = rtrim($baseUrl, '/').'/'.ltrim($signatureEndpoint, '/');

        try {
            $client = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-App-Code' => $clientCode,
                'X-Timestamp' => $timestamp,
                'X-Signature' => $signature,
            ])->timeout($timeout);

            if (! $verifySsl) {
                $client = $client->withoutVerifying();
            }

            $response = $client->withBody($body, 'application/json')->post($url);

            if (! $response->successful()) {
                $message = data_get($response->json(), 'message') ?: 'Portal menolak pembuatan QR signature.';
                throw ValidationException::withMessages(['signature' => $message]);
            }

            $sig = $response->json('data.signature') ?? $response->json('data') ?? [];
            $sigId = data_get($sig, 'portal_signature_id');
            $sigUrl = data_get($sig, 'portal_signature_url') ?? data_get($sig, 'portal_qr_payload.verification_url');

            if (! $sigId || ! $sigUrl) {
                throw ValidationException::withMessages(['signature' => 'Response QR signature Portal tidak lengkap.']);
            }

            return [
                'portal_signature_id' => $sigId,
                'portal_signature_url' => $sigUrl,
                'portal_qr_url' => $sigUrl,
            ];
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $e) {
            Log::warning('Portal signature request failed: '.$e->getMessage(), ['approval_id' => $approval->id]);
            throw ValidationException::withMessages(['signature' => 'QR signature Portal gagal dibuat. Approval belum diproses.']);
        }
    }
}
