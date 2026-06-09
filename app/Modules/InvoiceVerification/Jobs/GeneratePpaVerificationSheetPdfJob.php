<?php

namespace App\Modules\InvoiceVerification\Jobs;

use App\Modules\InvoiceVerification\Domain\Models\PpaVerificationSheet;
use App\Modules\InvoiceVerification\Services\Contracts\PpaVerificationSheetPdfGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GeneratePpaVerificationSheetPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $sheetId,
    ) {
    }

    public function handle(PpaVerificationSheetPdfGenerator $generator): void
    {
        $sheet = PpaVerificationSheet::with('transaction', 'items.documentType')->findOrFail($this->sheetId);
        $sheet->update($generator->generate($sheet));
    }
}
