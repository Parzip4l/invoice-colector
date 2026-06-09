<?php

namespace App\Modules\InvoiceVerification\Actions;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Services\FinalizationService;

class FinalizeTransactionAction
{
    public function __construct(
        protected FinalizationService $finalizationService,
    ) {
    }

    public function execute(Transaction $transaction, User $actor): void
    {
        $this->finalizationService->finalize($transaction, $actor);
    }
}
