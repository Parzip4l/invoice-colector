<?php

namespace App\Modules\InvoiceVerification\Jobs;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Services\FinalizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCompiledDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $transactionId,
        public int $actorId,
    ) {
    }

    public function handle(FinalizationService $finalizationService): void
    {
        $transaction = Transaction::findOrFail($this->transactionId);
        $actor = User::findOrFail($this->actorId);

        $finalizationService->finalize($transaction, $actor);
    }
}
