<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionStatusHistory;
use Illuminate\Validation\ValidationException;

class TransactionLifecycleService
{
    public function transition(
        Transaction $transaction,
        TransactionStatus $status,
        TransactionStep $step,
        ?User $actor = null,
        ?string $notes = null,
    ): Transaction {
        $this->validateTransition($transaction, $status, $actor);

        $originalStatus = $transaction->status;
        $originalStep = $transaction->current_step;

        $transaction->forceFill([
            'status' => $status,
            'current_step' => $step,
            'completed_at' => in_array($status, [TransactionStatus::COMPLETED, TransactionStatus::ARCHIVED], true)
                ? now()
                : $transaction->completed_at,
        ])->save();

        TransactionStatusHistory::create([
            'transaction_id' => $transaction->id,
            'from_status' => $originalStatus?->value,
            'to_status' => $status->value,
            'from_step' => $originalStep?->value,
            'to_step' => $step->value,
            'changed_by' => $actor?->id,
            'notes' => $notes,
        ]);

        return $transaction->refresh();
    }

    public function submitByVendor(Transaction $transaction, User $actor, ?string $notes = null): Transaction
    {
        return $this->transition(
            $transaction,
            TransactionStatus::SUBMITTED,
            TransactionStep::ACCOUNTING_VERIFICATION,
            $actor,
            $notes ?: 'Vendor submit transaksi untuk verifikasi Accounting.',
        );
    }

    public function startAccountingReview(Transaction $transaction, User $actor): Transaction
    {
        return $this->transition(
            $transaction,
            TransactionStatus::IN_REVIEW,
            TransactionStep::ACCOUNTING_VERIFICATION,
            $actor,
            'Accounting memulai verifikasi transaksi.',
        );
    }

    public function markReceived(Transaction $transaction, User $actor): Transaction
    {
        return $this->transition(
            $transaction,
            TransactionStatus::RECEIVED,
            TransactionStep::FINANCE_PROCESS,
            $actor,
            'Accounting menyatakan dokumen lengkap dan transaksi diteruskan ke Finance.',
        );
    }

    public function markNotApproved(Transaction $transaction, User $actor, ?string $notes = null): Transaction
    {
        return $this->transition(
            $transaction,
            TransactionStatus::NOT_APPROVED,
            TransactionStep::VENDOR_INVOICE_INPUT,
            $actor,
            $notes ?: 'Accounting meminta revisi kepada Vendor.',
        );
    }

    public function schedulePayment(Transaction $transaction, User $actor): Transaction
    {
        return $this->transition(
            $transaction,
            TransactionStatus::SCHEDULING_PAYMENT,
            TransactionStep::FINANCE_PROCESS,
            $actor,
            'Finance menjadwalkan pembayaran.',
        );
    }

    public function markPaid(Transaction $transaction, User $actor): Transaction
    {
        return $this->transition(
            $transaction,
            TransactionStatus::PAID,
            TransactionStep::FINANCE_PROCESS,
            $actor,
            'Finance menandai transaksi Paid.',
        );
    }

    private function validateTransition(Transaction $transaction, TransactionStatus $targetStatus, ?User $actor): void
    {
        $sourceStatus = $transaction->status;

        if (! $sourceStatus || $sourceStatus === $targetStatus) {
            return;
        }

        $activeStatuses = [
            TransactionStatus::DRAFT,
            TransactionStatus::SUBMITTED,
            TransactionStatus::IN_REVIEW,
            TransactionStatus::NOT_APPROVED,
            TransactionStatus::RECEIVED,
            TransactionStatus::SCHEDULING_PAYMENT,
            TransactionStatus::PAID,
        ];

        if (! in_array($sourceStatus, $activeStatuses, true) && ! in_array($targetStatus, $activeStatuses, true)) {
            return;
        }

        $allowed = [
            TransactionStatus::DRAFT->value => [TransactionStatus::SUBMITTED],
            TransactionStatus::NOT_APPROVED->value => [TransactionStatus::SUBMITTED],
            TransactionStatus::SUBMITTED->value => [TransactionStatus::IN_REVIEW],
            TransactionStatus::ACCOUNTING_VERIFICATION->value => [TransactionStatus::IN_REVIEW],
            TransactionStatus::IN_REVIEW->value => [TransactionStatus::NOT_APPROVED, TransactionStatus::RECEIVED],
            TransactionStatus::RECEIVED->value => [TransactionStatus::SCHEDULING_PAYMENT],
            TransactionStatus::SCHEDULING_PAYMENT->value => [TransactionStatus::PAID],
        ];

        if (! in_array($targetStatus, $allowed[$sourceStatus->value] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => sprintf('Transisi status dari %s ke %s tidak diperbolehkan.', $sourceStatus->label(), $targetStatus->label()),
            ]);
        }

        if (! $actor) {
            throw ValidationException::withMessages(['status' => 'Aktor wajib tersedia untuk perubahan status.']);
        }

        $vendorTransition = in_array($sourceStatus, [TransactionStatus::DRAFT, TransactionStatus::NOT_APPROVED], true)
            && $targetStatus === TransactionStatus::SUBMITTED;
        $accountingTransition = (
            ($sourceStatus === TransactionStatus::SUBMITTED && $targetStatus === TransactionStatus::IN_REVIEW)
            || ($sourceStatus === TransactionStatus::ACCOUNTING_VERIFICATION && $targetStatus === TransactionStatus::IN_REVIEW)
            || ($sourceStatus === TransactionStatus::IN_REVIEW && in_array($targetStatus, [TransactionStatus::NOT_APPROVED, TransactionStatus::RECEIVED], true))
        );
        $financeTransition = (
            ($sourceStatus === TransactionStatus::RECEIVED && $targetStatus === TransactionStatus::SCHEDULING_PAYMENT)
            || ($sourceStatus === TransactionStatus::SCHEDULING_PAYMENT && $targetStatus === TransactionStatus::PAID)
        );

        if ($vendorTransition) {
            $ownsTransaction = $transaction->owner_user_id === $actor->id
                || ($actor->hasRole(RoleCode::VENDOR) && $transaction->vendor_id === $actor->linkedVendor()?->id);

            if (! $ownsTransaction) {
                throw ValidationException::withMessages(['status' => 'Vendor hanya dapat submit transaksi miliknya.']);
            }

            return;
        }

        if ($accountingTransition && $actor->hasRole(RoleCode::AKUNTANSI)) {
            return;
        }

        if ($financeTransition && $actor->hasRole(RoleCode::FINANCE)) {
            return;
        }

        throw ValidationException::withMessages(['status' => 'Aktor tidak berwenang melakukan transisi status ini.']);
    }
}
