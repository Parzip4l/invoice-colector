<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;
use App\Modules\InvoiceVerification\Domain\Models\AuditLog;
use App\Modules\InvoiceVerification\Domain\Models\CompiledDocument;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionDocument;

class DashboardService
{
    public function summary(): array
    {
        return [
            'transactions_total' => Transaction::count(),
            'transactions_waiting_approval' => Transaction::where('status', TransactionStatus::WAITING_APPROVAL)->count(),
            'documents_pending_review' => TransactionDocument::where('status', 'UNDER_REVIEW')->count(),
            'transactions_finance_queue' => Transaction::where('status', TransactionStatus::FINANCE_PROCESS)->count(),
            'approval_queue' => ApprovalTransaction::where('status', ApprovalStatus::PENDING)->count(),
            'completed_transactions' => Transaction::whereIn('status', [TransactionStatus::COMPLETED, TransactionStatus::ARCHIVED])->count(),
            'compiled_documents' => CompiledDocument::count(),
            'audit_entries_today' => AuditLog::whereDate('created_at', today())->count(),
        ];
    }
}
