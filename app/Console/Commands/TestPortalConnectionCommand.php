<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Services\PortalApprovalResolverService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TestPortalConnectionCommand extends Command
{
    protected $signature = 'portal:test-connection {email? : Email pemohon untuk dites di Portal}';

    protected $description = 'Memeriksa dan menguji konektivitas signed request dari Invoice Collector ke Portal LRT Jakarta';

    public function handle(PortalApprovalResolverService $resolverService): int
    {
        $this->info('====================================================');
        $this->info('  UJI KONEKSI PORTAL APPROVAL RESOLVER LRT JAKARTA  ');
        $this->info('====================================================');

        $baseUrl = (string) config('invoice_verification.approval_resolver.base_url');
        $endpoint = (string) config('invoice_verification.approval_resolver.endpoint', '/api/v1/approval/resolve');
        $clientCode = (string) config('invoice_verification.approval_resolver.client_code', 'invoice_collector');
        $secret = (string) config('invoice_verification.approval_resolver.shared_secret');
        $timeout = (int) config('invoice_verification.approval_resolver.timeout', 15);
        $verifySsl = (bool) config('invoice_verification.approval_resolver.verify_ssl', true);

        $this->line("Base URL      : <comment>{$baseUrl}</comment>");
        $this->line("Endpoint      : <comment>{$endpoint}</comment>");
        $this->line("Client Code   : <comment>{$clientCode}</comment>");
        $maskedSecret = $secret !== '' ? Str::mask($secret, '*', 4, -4) : '<error>BELUM DIISI</error>';
        $this->line("Shared Secret : <comment>{$maskedSecret}</comment>");
        $this->line("Verify SSL    : <comment>".($verifySsl ? 'Ya' : 'Tidak')."</comment>");
        $this->newLine();

        if ($baseUrl === '') {
            $this->error('Gagal: INVOICE_COLLECTOR_APPROVAL_RESOLVER_BASE_URL belum diisi di file .env.');
            return self::FAILURE;
        }

        if ($secret === '') {
            $this->error('Gagal: INVOICE_COLLECTOR_PORTAL_SHARED_SECRET belum diisi di file .env.');
            $this->warn('Petunjuk: Buat secret dengan `openssl rand -hex 32` lalu daftarkan ke Portal.');
            return self::FAILURE;
        }

        $email = $this->argument('email')
            ?: User::where('role_code', RoleCode::USER_DIVISI)->value('email')
            ?: 'user.divisi@demo.local';

        $this->info("Menguji request resolver dengan email pemohon: <comment>{$email}</comment>...");

        $payload = [
            'module' => $clientCode,
            'request_type' => 'ppa_non_contract',
            'requester_email' => $email,
            'amount' => 10000000,
            'metadata' => [
                'transaction_id' => (string) Str::uuid(),
                'registration_number' => 'TEST-CONN-'.now()->format('YmdHis'),
            ],
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);
        $url = rtrim($baseUrl, '/').'/'.ltrim($endpoint, '/');

        $this->line("Target URL : {$url}");
        $this->line("Timestamp  : {$timestamp}");
        $this->line("Signature  : {$signature}");
        $this->newLine();

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
        } catch (\Throwable $e) {
            $this->error("Koneksi Gagal (Exception): {$e->getMessage()}");
            $this->warn('Periksa apakah domain server Portal dapat dijangkau dari server ini.');
            return self::FAILURE;
        }

        $status = $response->status();
        $this->line("HTTP Status Code: <comment>{$status}</comment>");

        if ($status === 200) {
            $this->info('STATUS: BERHASIL TERHUBUNG (200 OK)!');
            $data = $response->json('data') ?? [];
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        if ($status === 401) {
            $this->error('STATUS: GAGAL (401 Unauthorized)');
            $this->warn('Kemungkinan penyebab:');
            $this->line('1. Nilai INVOICE_COLLECTOR_PORTAL_SHARED_SECRET di .env berbeda dengan yang terdaftar di Portal.');
            $this->line("2. Client '{$clientCode}' belum didaftarkan di tabel `external_application_clients` pada database Portal.");
            $this->line('Response Body: '.$response->body());
            return self::FAILURE;
        }

        if ($status === 422) {
            $this->warn('STATUS: TERHUBUNG KE PORTAL, tetapi ditolak validasi data (422 Unprocessable Entity)');
            $this->line('Artinya: Kunci shared_secret dan signature SUDAH VALID, tetapi pemohon atau aturan approval belum ada di Portal.');
            $this->line('Response Body: '.$response->body());
            return self::SUCCESS;
        }

        $this->error("STATUS: Response HTTP {$status}");
        $this->line('Response Body: '.$response->body());

        return self::FAILURE;
    }
}
