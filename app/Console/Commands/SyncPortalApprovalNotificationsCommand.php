<?php

namespace App\Console\Commands;

use App\Modules\InvoiceVerification\Domain\Enums\AccountingVerificationStatus;
use App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Services\ApprovalWorkflowService;
use App\Modules\InvoiceVerification\Services\PortalApprovalResolverService;
use Illuminate\Console\Command;

class SyncPortalApprovalNotificationsCommand extends Command
{
    protected $signature = 'invoice-verification:sync-portal-approval-notifications {--dry-run : Simulasi tanpa mengirim atau memodifikasi data}';

    protected $description = 'Kirim atau sinkronkan notifikasi approval aktif Invoice Collector ke Portal (termasuk routing Kadiv Finance untuk berkas yang sudah diverifikasi akuntansi)';

    public function handle(PortalApprovalResolverService $portal, ApprovalWorkflowService $workflow): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        // 1. Cek transaksi PPA Non Kontrak yang verifikasi akuntansinya sudah COMPLETED/VALID,
        // tetapi approval Kadiv Finance (Step 3) belum aktif / belum diarahkan ke Portal.
        $this->info('Memeriksa transaksi PPA Non Kontrak yang telah disetujui akuntansi...');

        $candidateTransactions = Transaction::query()
            ->whereHas('transactionType', fn ($q) => $q->where('code', TransactionTypeCode::PPA_NON_CONTRACT->value))
            ->whereHas('accountingVerification', fn ($q) => $q->where('status', AccountingVerificationStatus::COMPLETED->value))
            ->whereNotIn('status', [TransactionStatus::PAID, TransactionStatus::COMPLETED, TransactionStatus::ARCHIVED])
            ->whereDoesntHave('approvalTransactions', function ($q) {
                $q->whereHas('approvalFlow', fn ($af) => $af->where('step_no', 3))
                    ->where('status', ApprovalStatus::APPROVED->value);
            })
            ->with(['accountingVerification', 'approvalTransactions.approvalFlow'])
            ->get();

        $routedCount = 0;
        foreach ($candidateTransactions as $transaction) {
            $hasActiveStep3 = $transaction->approvalTransactions
                ->first(fn ($a) => (int) $a->approvalFlow?->step_no === 3 && $a->status === ApprovalStatus::PENDING);

            if (! $hasActiveStep3 || $transaction->current_step !== TransactionStep::KADIV_FINANCE_REVIEW) {
                if ($isDryRun) {
                    $this->line("[Simulasi] Transaksi {$transaction->registration_number} akan diarahkan ke Kadiv Finance.");
                    $routedCount++;
                } else {
                    try {
                        $workflow->triggerKadivFinanceApproval($transaction);
                        $this->info("[Rute Kadiv Finance] Transaksi {$transaction->registration_number} berhasil diarahkan ke Kadiv Finance & notifikasi dikirim.");
                        $routedCount++;
                    } catch (\Throwable $e) {
                        $this->warn("[Lewati] Gagal mengarahkan transaksi {$transaction->registration_number} ke Kadiv Finance: {$e->getMessage()}");
                    }
                }
            }
        }

        // 2. Kirim notifikasi untuk seluruh approval yang berstatus PENDING pada step aktif
        $this->info('Menyinkronkan seluruh notifikasi approval aktif ke Portal...');
        $notifiedCount = 0;

        ApprovalTransaction::query()
            ->with(['transaction.owner', 'approvalFlow', 'approver'])
            ->where('status', ApprovalStatus::PENDING->value)
            ->orderBy('created_at')
            ->each(function (ApprovalTransaction $approval) use ($portal, $isDryRun, &$notifiedCount): void {
                $expectedStep = match ((int) $approval->approvalFlow?->step_no) {
                    1 => TransactionStep::KADEP_REVIEW,
                    2 => TransactionStep::KADIV_REVIEW,
                    3 => TransactionStep::KADIV_FINANCE_REVIEW,
                    default => null,
                };
                if ($expectedStep === null || $approval->transaction?->current_step !== $expectedStep) {
                    return;
                }

                if ($isDryRun) {
                    $this->line("[Simulasi Notifikasi] Approval #{$approval->id} ({$approval->transaction?->registration_number} - Step {$approval->approvalFlow?->step_no}) -> {$approval->approver?->email}");
                    $notifiedCount++;
                } else {
                    try {
                        $portal->notifyApprovalRequested($approval);
                        $this->line("[Notifikasi Terkirim] Approval #{$approval->id} ({$approval->transaction?->registration_number} - Step {$approval->approvalFlow?->step_no}) -> {$approval->approver?->email}");
                        $notifiedCount++;
                    } catch (\Throwable $e) {
                        $this->warn("[Gagal Kirim Notifikasi] Approval #{$approval->id} ({$approval->transaction?->registration_number}): {$e->getMessage()}");
                    }
                }
            });

        $this->newLine();
        $this->info("Sinkronisasi selesai: {$routedCount} transaksi diarahkan ke Kadiv Finance, {$notifiedCount} notifikasi approval aktif diproses ke Portal.");

        return self::SUCCESS;
    }
}
