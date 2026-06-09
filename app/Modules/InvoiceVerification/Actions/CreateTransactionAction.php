<?php

namespace App\Modules\InvoiceVerification\Actions;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Models\AgreementReference;
use App\Modules\InvoiceVerification\Domain\Models\MemoRequest;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionParty;
use App\Modules\InvoiceVerification\Services\AuditLogService;
use App\Modules\InvoiceVerification\Services\RegistrationNumberService;
use App\Modules\InvoiceVerification\Services\TransactionLifecycleService;
use Illuminate\Support\Facades\DB;

class CreateTransactionAction
{
    public function __construct(
        protected RegistrationNumberService $registrationNumberService,
        protected TransactionLifecycleService $transactionLifecycleService,
        protected AuditLogService $auditLogService,
    ) {
    }

    public function execute(array $payload, User $actor): Transaction
    {
        return DB::transaction(function () use ($payload, $actor) {
            $memoRequest = MemoRequest::query()->findOrFail($payload['memo_request_id']);
            $agreementReference = ! empty($payload['agreement_reference_id'])
                ? AgreementReference::query()->findOrFail($payload['agreement_reference_id'])
                : null;
            $title = sprintf(
                '%s - %s',
                $agreementReference?->contract_number ?? ($memoRequest->memo_number ?? 'Draft Tagihan'),
                $payload['description'] ?? 'Draft Tagihan Vendor'
            );

            $transaction = Transaction::create([
                'registration_number' => $this->registrationNumberService->generateTransactionNumber(),
                'transaction_type_id' => $payload['transaction_type_id'],
                'vendor_id' => $payload['vendor_id'] ?? null,
                'division_id' => $payload['division_id'],
                'department_id' => $payload['department_id'],
                'memo_request_id' => $memoRequest->id,
                'agreement_reference_id' => $payload['agreement_reference_id'] ?? null,
                'title' => $title,
                'description' => $payload['description'] ?? null,
                'contract_number' => $agreementReference?->contract_number,
                'contract_value' => $agreementReference?->contract_value,
                'status' => TransactionStatus::DRAFT,
                'current_step' => TransactionStep::VENDOR_INVOICE_INPUT,
                'created_by' => $actor->id,
                'submitted_at' => null,
            ]);

            TransactionParty::create([
                'transaction_id' => $transaction->id,
                'party_type' => 'CREATOR',
                'user_id' => $actor->id,
                'status' => 'ACTIVE',
            ]);

            if (! empty($payload['vendor_id'])) {
                TransactionParty::create([
                    'transaction_id' => $transaction->id,
                    'party_type' => 'VENDOR',
                    'vendor_id' => $payload['vendor_id'],
                    'status' => 'ACTIVE',
                ]);
            }

            $this->transactionLifecycleService->transition(
                $transaction,
                TransactionStatus::DRAFT,
                TransactionStep::VENDOR_INVOICE_INPUT,
                $actor,
                'Draft tagihan dibuat oleh Admin User dan menunggu input tagihan dari Vendor.',
            );

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
}
