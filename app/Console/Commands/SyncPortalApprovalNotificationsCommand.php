<?php

namespace App\Console\Commands;

use App\Modules\InvoiceVerification\Domain\Enums\AccountingVerificationStatus;
use App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionStatusHistory;
use App\Modules\InvoiceVerification\Services\ApprovalWorkflowService;
use App\Modules\InvoiceVerification\Services\FinalizationService;
use App\Modules\InvoiceVerification\Services\NumberingRegisterService;
use App\Modules\InvoiceVerification\Services\PortalApprovalResolverService;
use App\Modules\InvoiceVerification\Services\PpaVerificationSheetService;
use Illuminate\Console\Command;

class SyncPortalApprovalNotificationsCommand extends Command
{
    protected $signature = 'invoice-verification:sync-portal-approval-notifications
        {--dry-run : Simulasi tanpa mengirim atau memodifikasi data}
        {--mark-approved= : Nomor registrasi transaksi (misal: PNK-00005) yang sudah diapprove di Portal untuk diselesaikan}';

    protected $description = 'Kirim atau sinkronkan notifikasi approval aktif Invoice Collector ke Portal, rute Kadiv Finance, dan selesaikan transaksi yang telah disetujui Kadiv Finance';

    public function handle(PortalApprovalResolverService $portal, ApprovalWorkflowService $workflow): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $markApprovedReg = trim((string) $this->option('mark-approved'));

        // 0. Jika ada transaksi spesifik yang ingin ditandai approved dari Portal
        if ($markApprovedReg !== '') {
            $this->info("Memproses penyelesaian manual dari Portal untuk transaksi {$markApprovedReg}...");
            $tx = Transaction::query()
                ->where('registration_number', $markApprovedReg)
                ->with(['approvalTransactions.approvalFlow', 'approvalTransactions.approver', 'owner'])
                ->first();

            if (! $tx) {
                $this->error("Transaksi {$markApprovedReg} tidak ditemukan.");

                return self::FAILURE;
            }

            $step3Approval = $tx->approvalTransactions
                ->first(fn ($a) => (int) $a->approvalFlow?->step_no === 3);

            if (! $step3Approval) {
                $this->error("Approval Step 3 (Kadiv Finance) tidak ditemukan pada transaksi {$markApprovedReg}.");

                return self::FAILURE;
            }

            $actor = $step3Approval->approver ?? $tx->owner;

            if ($isDryRun) {
                $this->line("[Simulasi] Transaksi {$markApprovedReg} akan diselesaikan persetujuan Kadiv Finance-nya.");
            } else {
                if ($step3Approval->status !== ApprovalStatus::APPROVED) {
                    try {
                        $signatureData = $portal->requestPortalSignature($step3Approval, $actor, 'Persetujuan disinkronkan dari Portal.');
                    } catch (\Throwable $e) {
                        $signatureData = null;
                        $this->warn("Gagal meminta signature Portal: {$e->getMessage()} (melanjutkan proses approval)");
                    }

                    $step3Approval->forceFill([
                        'status' => ApprovalStatus::APPROVED,
                        'notes' => 'Disetujui di Portal dan disinkronkan ke Invoice Collector.',
                        'action_at' => now(),
                        'portal_signature_id' => $signatureData['portal_signature_id'] ?? $step3Approval->portal_signature_id,
                        'portal_signature_url' => $signatureData['portal_signature_url'] ?? $step3Approval->portal_signature_url,
                        'portal_qr_url' => $signatureData['portal_qr_url'] ?? $step3Approval->portal_qr_url,
                    ])->save();
                }

                $fromStatus = $tx->status?->value;
                $fromStep = $tx->current_step?->value;

                $tx->forceFill([
                    'status' => TransactionStatus::COMPLETED,
                    'current_step' => TransactionStep::COMPLETED,
                    'completed_at' => $tx->completed_at ?? now(),
                ])->save();

                TransactionStatusHistory::create([
                    'transaction_id' => $tx->id,
                    'from_status' => $fromStatus,
                    'to_status' => TransactionStatus::COMPLETED->value,
                    'from_step' => $fromStep,
                    'to_step' => TransactionStep::COMPLETED->value,
                    'changed_by' => $actor?->id,
                    'notes' => 'Persetujuan Kadiv Finance disinkronkan. Transaksi selesai.',
                ]);

                app(NumberingRegisterService::class)->syncFromTransaction($tx);
                if ($actor) {
                    app(FinalizationService::class)->registerCompilationManifest($tx, $actor);
                }

                try {
                    app(PpaVerificationSheetService::class)->regeneratePdf($tx);
                } catch (\Throwable $e) {
                    $this->warn("Gagal regenerate PDF: {$e->getMessage()}");
                }

                $this->info("Transaksi {$markApprovedReg} berhasil disetujui & diselesaikan (COMPLETED)!");
            }
        }

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

        // 3. Selesaikan transaksi PPA Non Kontrak yang Step 3-nya sudah APPROVED,
        // tetapi status transaksi atau step-nya belum COMPLETED / Selesai.
        $this->info('Memeriksa transaksi PPA Non Kontrak yang telah disetujui Kadiv Finance tetapi belum berstatus Selesai...');
        $completedCount = 0;

        $uncompletedApprovedTxs = Transaction::query()
            ->whereHas('transactionType', fn ($q) => $q->where('code', TransactionTypeCode::PPA_NON_CONTRACT->value))
            ->whereHas('approvalTransactions', function ($q) {
                $q->whereHas('approvalFlow', fn ($af) => $af->where('step_no', 3))
                    ->where('status', ApprovalStatus::APPROVED->value);
            })
            ->where(function ($q) {
                $q->where('status', '!=', TransactionStatus::COMPLETED->value)
                    ->orWhere('current_step', '!=', TransactionStep::COMPLETED->value)
                    ->orWhereNull('completed_at');
            })
            ->with(['approvalTransactions.approvalFlow', 'approvalTransactions.approver', 'owner'])
            ->get();

        foreach ($uncompletedApprovedTxs as $transaction) {
            $step3Approval = $transaction->approvalTransactions
                ->first(fn ($a) => (int) $a->approvalFlow?->step_no === 3 && $a->status === ApprovalStatus::APPROVED);

            if ($isDryRun) {
                $this->line("[Simulasi Selesai] Transaksi {$transaction->registration_number} akan diperbarui ke Selesai (COMPLETED).");
                $completedCount++;
            } else {
                try {
                    $actor = $step3Approval?->approver ?? $transaction->owner;
                    $completedAt = $step3Approval?->action_at ?? now();

                    $fromStatus = $transaction->status?->value;
                    $fromStep = $transaction->current_step?->value;

                    $transaction->forceFill([
                        'status' => TransactionStatus::COMPLETED,
                        'current_step' => TransactionStep::COMPLETED,
                        'completed_at' => $transaction->completed_at ?? $completedAt,
                    ])->save();

                    TransactionStatusHistory::create([
                        'transaction_id' => $transaction->id,
                        'from_status' => $fromStatus,
                        'to_status' => TransactionStatus::COMPLETED->value,
                        'from_step' => $fromStep,
                        'to_step' => TransactionStep::COMPLETED->value,
                        'changed_by' => $actor?->id,
                        'notes' => 'Sinkronisasi status selesai setelah persetujuan Kepala Divisi Keuangan dan Akuntansi.',
                    ]);

                    app(NumberingRegisterService::class)->syncFromTransaction($transaction);
                    if ($actor) {
                        app(FinalizationService::class)->registerCompilationManifest($transaction, $actor);
                    }

                    try {
                        app(PpaVerificationSheetService::class)->regeneratePdf($transaction);
                    } catch (\Throwable $e) {
                        $this->warn("[Warning PDF] Gagal regenerate PDF {$transaction->registration_number}: {$e->getMessage()}");
                    }

                    $this->info("[Selesai] Transaksi {$transaction->registration_number} berhasil diselesaikan (Status: COMPLETED, Step: Selesai).");
                    $completedCount++;
                } catch (\Throwable $e) {
                    $this->warn("[Gagal] Transaksi {$transaction->registration_number}: {$e->getMessage()}");
                }
            }
        }

        $this->newLine();
        $this->info("Sinkronisasi selesai: {$routedCount} transaksi diarahkan ke Kadiv Finance, {$notifiedCount} notifikasi approval aktif diproses ke Portal, {$completedCount} transaksi diselesaikan ke Selesai (COMPLETED).");

        return self::SUCCESS;
    }
}
