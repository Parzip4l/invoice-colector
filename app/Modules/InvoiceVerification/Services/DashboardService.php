<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Models\AgreementReference;
use App\Modules\InvoiceVerification\Domain\Models\AuditLog;
use App\Modules\InvoiceVerification\Domain\Models\CompiledDocument;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionDocument;
use Carbon\CarbonImmutable;

class DashboardService
{
    public function vendorDashboard(User $user): array
    {
        $vendor = $user->linkedVendor();
        $baseQuery = Transaction::query()
            ->with(['transactionType', 'vendor', 'invoiceMetadata'])
            ->when($vendor, fn ($query) => $query->where('vendor_id', $vendor->id))
            ->when(! $vendor, fn ($query) => $query->whereRaw('1 = 0'));

        $transactions = (clone $baseQuery)->latest()->get();
        $recentTransactions = $transactions->take(6)->values();
        $amountTotal = (float) $transactions->sum(fn (Transaction $transaction) => $this->transactionAmount($transaction));
        $completed = $transactions->whereIn('status', [
            TransactionStatus::PAID,
            TransactionStatus::COMPLETED,
            TransactionStatus::ARCHIVED,
        ])->count();

        $statusDistribution = collect(TransactionStatus::cases())
            ->map(fn (TransactionStatus $status) => [
                'label' => $status->label(),
                'value' => $transactions->where('status', $status)->count(),
            ])
            ->filter(fn (array $item) => $item['value'] > 0)
            ->values()
            ->all();

        return [
            'vendor' => $vendor,
            'summary' => [
                'total' => $transactions->count(),
                'draft' => $transactions->where('status', TransactionStatus::DRAFT)->count(),
                'submitted' => $transactions->where('status', TransactionStatus::SUBMITTED)->count(),
                'in_review' => $transactions->where('status', TransactionStatus::IN_REVIEW)->count(),
                'not_approved' => $transactions->where('status', TransactionStatus::NOT_APPROVED)->count(),
                'received' => $transactions->where('status', TransactionStatus::RECEIVED)->count(),
                'paid' => $transactions->where('status', TransactionStatus::PAID)->count(),
                'completion_rate' => $transactions->count() > 0 ? round(($completed / $transactions->count()) * 100, 1) : 0,
                'amount_total' => $amountTotal,
            ],
            'recentTransactions' => $recentTransactions,
            'analytics' => [
                'trend' => $this->transactionCountTrend($transactions),
                'status_distribution' => $statusDistribution,
                'amount_monthly' => $this->amountTrendFromTransactions($transactions, 'monthly'),
            ],
        ];
    }

    public function summary(): array
    {
        return [
            'transactions_total' => Transaction::count(),
            'transactions_submitted' => Transaction::where('status', TransactionStatus::SUBMITTED)->count(),
            'transactions_in_review' => Transaction::where('status', TransactionStatus::IN_REVIEW)->count(),
            'documents_pending_review' => TransactionDocument::where('status', 'UNDER_REVIEW')->count(),
            'transactions_finance_queue' => Transaction::whereIn('status', [TransactionStatus::RECEIVED, TransactionStatus::SCHEDULING_PAYMENT])->count(),
            'transactions_paid' => Transaction::where('status', TransactionStatus::PAID)->count(),
            'completed_transactions' => Transaction::whereIn('status', [TransactionStatus::PAID, TransactionStatus::COMPLETED, TransactionStatus::ARCHIVED])->count(),
            'compiled_documents' => CompiledDocument::count(),
            'audit_entries_today' => AuditLog::whereDate('created_at', today())->count(),
        ];
    }

    private function transactionCountTrend($transactions): array
    {
        $now = CarbonImmutable::now();
        $trendStart = $now->startOfMonth()->subMonths(5);
        $monthlyTransactions = $transactions
            ->filter(fn (Transaction $transaction) => $transaction->created_at >= $trendStart)
            ->groupBy(fn (Transaction $transaction) => $transaction->created_at->format('Y-m'))
            ->map
            ->count();

        return collect(range(0, 5))->map(function (int $monthOffset) use ($trendStart, $monthlyTransactions) {
            $month = $trendStart->addMonths($monthOffset);

            return [
                'label' => $month->translatedFormat('M'),
                'value' => (int) ($monthlyTransactions[$month->format('Y-m')] ?? 0),
            ];
        })->values()->all();
    }

    private function amountTrendFromTransactions($transactions, string $period): array
    {
        $now = CarbonImmutable::now();
        $start = $now->startOfMonth()->subMonths(5);
        $slots = collect(range(0, 5))->map(fn (int $offset) => $start->addMonths($offset));

        $amounts = $transactions
            ->filter(fn (Transaction $transaction) => $transaction->created_at >= $start)
            ->groupBy(fn (Transaction $transaction) => $transaction->created_at->format('Y-m'))
            ->map(fn ($items) => $items->sum(fn (Transaction $transaction) => $this->transactionAmount($transaction)));

        return $slots
            ->map(fn (CarbonImmutable $slot) => [
                'label' => $slot->translatedFormat('M Y'),
                'value' => (float) ($amounts[$slot->format('Y-m')] ?? 0),
            ])
            ->values()
            ->all();
    }

    public function analytics(): array
    {
        $now = CarbonImmutable::now();
        $currentPeriodStart = $now->subDays(30);
        $previousPeriodStart = $now->subDays(60);

        $totalTransactions = Transaction::count();
        $completedTransactions = Transaction::whereIn('status', [TransactionStatus::PAID, TransactionStatus::COMPLETED, TransactionStatus::ARCHIVED])->count();
        $pendingTransactions = Transaction::whereNotIn('status', [TransactionStatus::PAID, TransactionStatus::COMPLETED, TransactionStatus::ARCHIVED])->count();
        $currentPeriodTransactions = Transaction::where('created_at', '>=', $currentPeriodStart)->count();
        $previousPeriodTransactions = Transaction::whereBetween('created_at', [$previousPeriodStart, $currentPeriodStart])->count();

        $statusDistribution = collect(TransactionStatus::cases())
            ->map(fn (TransactionStatus $status) => [
                'label' => $status->label(),
                'value' => Transaction::where('status', $status)->count(),
            ])
            ->filter(fn (array $item) => $item['value'] > 0)
            ->values();

        $trendStart = $now->startOfMonth()->subMonths(5);
        $monthlyTransactions = Transaction::where('created_at', '>=', $trendStart)
            ->get(['created_at'])
            ->groupBy(fn (Transaction $transaction) => $transaction->created_at->format('Y-m'))
            ->map
            ->count();

        $trend = collect(range(0, 5))->map(function (int $monthOffset) use ($trendStart, $monthlyTransactions) {
            $month = $trendStart->addMonths($monthOffset);

            return [
                'label' => $month->translatedFormat('M'),
                'value' => (int) ($monthlyTransactions[$month->format('Y-m')] ?? 0),
            ];
        });

        $topVendors = Transaction::query()
            ->with('vendor:id,name')
            ->whereNotNull('vendor_id')
            ->get(['id', 'vendor_id'])
            ->groupBy('vendor_id')
            ->map(fn ($transactions) => [
                'label' => $transactions->first()->vendor?->name ?? 'Vendor Tidak Diketahui',
                'value' => $transactions->count(),
            ])
            ->sortByDesc('value')
            ->take(5)
            ->values();
        $agreementSummary = $this->agreementSummary();

        return [
            'trend' => $trend->values()->all(),
            'status_distribution' => $statusDistribution->all(),
            'amount_weekly' => $this->amountTrend('weekly'),
            'amount_monthly' => $this->amountTrend('monthly'),
            'amount_yearly' => $this->amountTrend('yearly'),
            'top_vendors' => $topVendors->all(),
            'agreement_summary' => $agreementSummary,
            'insights' => [
                'period_change' => $this->percentageChange($currentPeriodTransactions, $previousPeriodTransactions),
                'total_pending' => $pendingTransactions,
                'completion_rate' => $totalTransactions > 0 ? round(($completedTransactions / $totalTransactions) * 100, 1) : 0,
                'nominal_total' => $this->transactionAmountTotal(),
            ],
        ];
    }

    private function amountTrend(string $period): array
    {
        $now = CarbonImmutable::now();

        [$start, $slots, $keyResolver, $labelResolver] = match ($period) {
            'weekly' => [
                $now->startOfWeek()->subWeeks(7),
                collect(range(0, 7))->map(fn (int $offset) => $now->startOfWeek()->subWeeks(7)->addWeeks($offset)),
                fn (Transaction $transaction) => CarbonImmutable::parse($transaction->created_at)->startOfWeek()->format('Y-m-d'),
                fn (CarbonImmutable $date) => $date->format('d M'),
            ],
            'yearly' => [
                $now->startOfYear()->subYears(4),
                collect(range(0, 4))->map(fn (int $offset) => $now->startOfYear()->subYears(4)->addYears($offset)),
                fn (Transaction $transaction) => CarbonImmutable::parse($transaction->created_at)->format('Y'),
                fn (CarbonImmutable $date) => $date->format('Y'),
            ],
            default => [
                $now->startOfMonth()->subMonths(11),
                collect(range(0, 11))->map(fn (int $offset) => $now->startOfMonth()->subMonths(11)->addMonths($offset)),
                fn (Transaction $transaction) => CarbonImmutable::parse($transaction->created_at)->format('Y-m'),
                fn (CarbonImmutable $date) => $date->translatedFormat('M Y'),
            ],
        };

        $amounts = Transaction::query()
            ->with('invoiceMetadata')
            ->where('created_at', '>=', $start)
            ->get()
            ->groupBy($keyResolver)
            ->map(fn ($transactions) => $transactions->sum(fn (Transaction $transaction) => $this->transactionAmount($transaction)));

        return $slots
            ->map(fn (CarbonImmutable $slot) => [
                'label' => $labelResolver($slot),
                'value' => (float) ($amounts[$period === 'weekly' ? $slot->format('Y-m-d') : ($period === 'yearly' ? $slot->format('Y') : $slot->format('Y-m'))] ?? 0),
            ])
            ->values()
            ->all();
    }

    private function transactionAmountTotal(): float
    {
        return (float) Transaction::query()
            ->with('invoiceMetadata')
            ->get()
            ->sum(fn (Transaction $transaction) => $this->transactionAmount($transaction));
    }

    private function agreementSummary(): array
    {
        $totalValue = (float) AgreementReference::query()->sum('contract_value');
        $agreementTransactions = Transaction::query()
            ->with('invoiceMetadata')
            ->whereNotNull('agreement_reference_id')
            ->get();
        $billedValue = (float) $agreementTransactions
            ->sum(fn (Transaction $transaction) => $this->transactionAmount($transaction));
        $paidValue = (float) $agreementTransactions
            ->filter(fn (Transaction $transaction) => in_array($transaction->status, [
                TransactionStatus::PAID,
                TransactionStatus::COMPLETED,
                TransactionStatus::ARCHIVED,
            ], true))
            ->sum(fn (Transaction $transaction) => $this->transactionAmount($transaction));

        return [
            'total_value' => $totalValue,
            'billed_value' => $billedValue,
            'paid_value' => $paidValue,
            'unbilled_value' => max($totalValue - $billedValue, 0),
            'outstanding_value' => max($billedValue - $paidValue, 0),
            'agreement_count' => AgreementReference::query()->count(),
            'billed_transaction_count' => $agreementTransactions->count(),
            'paid_transaction_count' => $agreementTransactions
                ->filter(fn (Transaction $transaction) => in_array($transaction->status, [
                    TransactionStatus::PAID,
                    TransactionStatus::COMPLETED,
                    TransactionStatus::ARCHIVED,
                ], true))
                ->count(),
        ];
    }

    private function transactionAmount(Transaction $transaction): float
    {
        foreach ([
            $transaction->invoiceMetadata?->invoice_value,
            $transaction->contract_value,
            $transaction->spu_amount,
            $transaction->accountability_amount,
            $transaction->petty_cash_top_up_amount,
        ] as $amount) {
            if ((float) $amount > 0) {
                return (float) $amount;
            }
        }

        return 0.0;
    }

    private function percentageChange(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
