<?php

namespace App\Console\Commands;

use App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;
use App\Modules\InvoiceVerification\Services\PortalApprovalResolverService;
use Illuminate\Console\Command;

class SyncPortalApprovalNotificationsCommand extends Command
{
    protected $signature = 'invoice-verification:sync-portal-approval-notifications';

    protected $description = 'Kirim atau sinkronkan notifikasi approval aktif Invoice Collector ke Portal';

    public function handle(PortalApprovalResolverService $portal): int
    {
        $count = 0;
        ApprovalTransaction::query()
            ->with(['transaction.owner', 'approvalFlow', 'approver'])
            ->where('status', ApprovalStatus::PENDING->value)
            ->orderBy('created_at')
            ->each(function (ApprovalTransaction $approval) use ($portal, &$count): void {
                $expectedStep = (int) $approval->approvalFlow?->step_no === 1
                    ? TransactionStep::KADEP_REVIEW
                    : TransactionStep::KADIV_REVIEW;
                if ($approval->transaction?->current_step !== $expectedStep) {
                    return;
                }

                $portal->notifyApprovalRequested($approval);
                $count++;
            });

        $this->info("{$count} approval aktif dikirim ke notifikasi Portal.");

        return self::SUCCESS;
    }
}
