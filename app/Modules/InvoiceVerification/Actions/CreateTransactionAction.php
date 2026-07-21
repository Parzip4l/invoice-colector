<?php

namespace App\Modules\InvoiceVerification\Actions;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode;
use App\Modules\InvoiceVerification\Domain\Models\AgreementReference;
use App\Modules\InvoiceVerification\Domain\Models\MemoRequest;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionParty;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use App\Modules\InvoiceVerification\Services\AuditLogService;
use App\Modules\InvoiceVerification\Services\Contracts\EprocDataProviderInterface;
use App\Modules\InvoiceVerification\Services\RegistrationNumberService;
use App\Modules\InvoiceVerification\Services\TransactionLifecycleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTransactionAction
{
    public function __construct(
        protected RegistrationNumberService $registrationNumberService,
        protected TransactionLifecycleService $transactionLifecycleService,
        protected AuditLogService $auditLogService,
        protected EprocDataProviderInterface $eprocDataProvider,
    ) {
    }

    public function execute(array $payload, User $actor): Transaction
    {
        return DB::transaction(function () use ($payload, $actor) {
            $transactionType = TransactionType::query()->findOrFail($payload['transaction_type_id']);
            $memoRequest = MemoRequest::query()->findOrFail($payload['memo_request_id']);
            $agreementReference = ! empty($payload['agreement_reference_id'])
                ? $this->eprocDataProvider->getContract($payload['agreement_reference_id'])
                : null;
            $parentSpu = ! empty($payload['parent_spu_transaction_id'])
                ? Transaction::query()->with('transactionType')->findOrFail($payload['parent_spu_transaction_id'])
                : null;
            $vendorId = $actor->linkedVendor()?->id ?? ($payload['vendor_id'] ?? null);
            $activityName = $parentSpu?->activity_name ?? ($payload['activity_name'] ?? null);
            $spuAmount = $parentSpu?->spu_amount ?? ($payload['spu_amount'] ?? null);
            $accountabilityAmount = $payload['accountability_amount'] ?? null;
            $remainingAmount = $parentSpu && $accountabilityAmount !== null
                ? $this->subtractDecimal($parentSpu->spu_amount, $accountabilityAmount)
                : null;
            $pettyCashCeiling = $transactionType->code->value === 'KAS_KECIL'
                ? $actor->division?->petty_cash_ceiling
                : null;
            $pettyCashRemaining = $payload['petty_cash_remaining_amount'] ?? null;
            $pettyCashTopUp = $pettyCashCeiling !== null && $pettyCashRemaining !== null
                ? $this->subtractDecimal($pettyCashCeiling, $pettyCashRemaining)
                : null;
            $title = sprintf(
                '%s - %s',
                $agreementReference?->contract_number ?? $parentSpu?->registration_number ?? ($memoRequest->memo_number ?? 'Draft Tagihan'),
                $activityName ?? $payload['description'] ?? 'Draft Tagihan Vendor'
            );

            $transaction = Transaction::create([
                'registration_number' => $this->registrationNumberService->generateTransactionNumber($transactionType),
                'transaction_type_id' => $transactionType->id,
                'vendor_id' => $vendorId,
                'division_id' => $payload['division_id'],
                'department_id' => $payload['department_id'],
                'memo_request_id' => $memoRequest->id,
                'agreement_reference_id' => $payload['agreement_reference_id'] ?? null,
                'parent_spu_transaction_id' => $parentSpu?->id,
                'title' => $title,
                'activity_name' => $activityName,
                'transaction_bank_name' => $payload['transaction_bank_name'] ?? $actor->linkedVendor()?->defaultBank?->name,
                'transaction_account_number' => $payload['transaction_account_number'] ?? $actor->linkedVendor()?->default_account_number,
                'description' => $payload['description'] ?? null,
                'contract_number' => $agreementReference?->contract_number,
                'contract_value' => $agreementReference?->contract_value,
                'spu_amount' => $spuAmount,
                'accountability_amount' => $accountabilityAmount,
                'remaining_amount' => $remainingAmount,
                'petty_cash_ceiling_snapshot' => $pettyCashCeiling,
                'petty_cash_remaining_amount' => $pettyCashRemaining,
                'petty_cash_top_up_amount' => $pettyCashTopUp,
                'period' => $payload['period'] ?? null,
                'status' => TransactionStatus::DRAFT,
                'current_step' => TransactionStep::VENDOR_INVOICE_INPUT,
                'created_by' => $actor->id,
                'owner_user_id' => $actor->id,
                'submitted_at' => null,
            ]);

            TransactionParty::create([
                'transaction_id' => $transaction->id,
                'party_type' => 'CREATOR',
                'user_id' => $actor->id,
                'status' => 'ACTIVE',
            ]);

            if (! empty($vendorId)) {
                TransactionParty::create([
                    'transaction_id' => $transaction->id,
                    'party_type' => 'VENDOR',
                    'vendor_id' => $vendorId,
                    'status' => 'ACTIVE',
                ]);
            }

            $this->auditLogService->log(
                module: 'transactions',
                action: 'create_transaction',
                actor: $actor,
                transaction: $transaction,
                referenceType: Transaction::class,
                referenceId: $transaction->id,
                newValue: [
                    'registration_number' => $transaction->registration_number,
                    'status' => TransactionStatus::DRAFT->value,
                ],
            );

            return $transaction->fresh([
                'transactionType',
                'vendor',
                'division',
                'department',
                'memoRequest',
                'invoiceMetadata',
                'generatedDocuments',
            ]);
        });
    }

    public function executeFromAgreement(AgreementReference $agreementReference, User $actor): Transaction
    {
        return DB::transaction(function () use ($agreementReference, $actor) {
            $agreementReference->loadMissing('vendor.defaultBank', 'division', 'department');
            $transactionType = TransactionType::query()
                ->where('code', TransactionTypeCode::PPA->value)
                ->firstOrFail();
            $vendor = $actor->linkedVendor();
            $divisionId = $agreementReference->division_id ?? $actor->division_id;
            $departmentId = $agreementReference->department_id ?? $actor->department_id;

            if (! $vendor || $agreementReference->vendor_id !== $vendor->id) {
                throw ValidationException::withMessages([
                    'agreement' => 'Kontrak tidak terhubung dengan akun vendor ini.',
                ]);
            }

            if (! $divisionId || ! $departmentId) {
                throw ValidationException::withMessages([
                    'agreement' => 'Kontrak belum memiliki divisi dan departemen. Hubungi Admin Divisi untuk melengkapi master agreement.',
                ]);
            }

            $title = sprintf(
                '%s - %s',
                $agreementReference->contract_number,
                $agreementReference->title ?: 'Tagihan Vendor'
            );

            $transaction = Transaction::create([
                'registration_number' => $this->registrationNumberService->generateTransactionNumber($transactionType),
                'transaction_type_id' => $transactionType->id,
                'vendor_id' => $vendor->id,
                'division_id' => $divisionId,
                'department_id' => $departmentId,
                'memo_request_id' => null,
                'agreement_reference_id' => $agreementReference->id,
                'parent_spu_transaction_id' => null,
                'title' => $title,
                'activity_name' => $agreementReference->title,
                'transaction_bank_name' => $vendor->defaultBank?->name,
                'transaction_account_number' => $vendor->default_account_number,
                'description' => $agreementReference->title,
                'contract_number' => $agreementReference->contract_number,
                'contract_value' => $agreementReference->contract_value,
                'spu_amount' => null,
                'accountability_amount' => null,
                'remaining_amount' => null,
                'petty_cash_ceiling_snapshot' => null,
                'petty_cash_remaining_amount' => null,
                'petty_cash_top_up_amount' => null,
                'period' => null,
                'status' => TransactionStatus::DRAFT,
                'current_step' => TransactionStep::VENDOR_INVOICE_INPUT,
                'created_by' => $actor->id,
                'owner_user_id' => $actor->id,
                'submitted_at' => null,
            ]);

            TransactionParty::create([
                'transaction_id' => $transaction->id,
                'party_type' => 'CREATOR',
                'user_id' => $actor->id,
                'status' => 'ACTIVE',
            ]);

            TransactionParty::create([
                'transaction_id' => $transaction->id,
                'party_type' => 'VENDOR',
                'vendor_id' => $vendor->id,
                'status' => 'ACTIVE',
            ]);

            $this->auditLogService->log(
                module: 'transactions',
                action: 'create_vendor_contract_transaction',
                actor: $actor,
                transaction: $transaction,
                referenceType: AgreementReference::class,
                referenceId: $agreementReference->id,
                newValue: [
                    'registration_number' => $transaction->registration_number,
                    'contract_number' => $agreementReference->contract_number,
                    'status' => TransactionStatus::DRAFT->value,
                ],
            );

            return $transaction->fresh([
                'transactionType',
                'vendor',
                'division',
                'department',
                'agreementReference',
                'invoiceMetadata',
                'generatedDocuments',
            ]);
        });
    }

    private function subtractDecimal(string|int|float|null $left, string|int|float|null $right): string
    {
        $leftCents = $this->decimalToCents($left);
        $rightCents = $this->decimalToCents($right);
        $result = $leftCents - $rightCents;
        $sign = $result < 0 ? '-' : '';
        $result = abs($result);

        return $sign.intdiv($result, 100).'.'.str_pad((string) ($result % 100), 2, '0', STR_PAD_LEFT);
    }

    private function decimalToCents(string|int|float|null $value): int
    {
        $normalized = preg_replace('/[^0-9\-.]/', '', (string) ($value ?? '0')) ?: '0';
        $negative = str_starts_with($normalized, '-');
        $normalized = ltrim($normalized, '-');
        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '0');
        $cents = ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');

        return $negative ? -$cents : $cents;
    }
}
