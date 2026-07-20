<?php

namespace App\Providers;

use App\Modules\InvoiceVerification\Services\Contracts\DocumentCompiler;
use App\Modules\InvoiceVerification\Services\Contracts\EprocDataProviderInterface;
use App\Modules\InvoiceVerification\Services\Contracts\LdapDirectorySynchronizer;
use App\Modules\InvoiceVerification\Services\Contracts\PpaVerificationSheetPdfGenerator;
use App\Modules\InvoiceVerification\Services\Eproc\ApiEprocDataProvider;
use App\Modules\InvoiceVerification\Services\Eproc\LocalEprocDataProvider;
use App\Modules\InvoiceVerification\Services\Implementations\FunctionalDocumentCompiler;
use App\Modules\InvoiceVerification\Services\Implementations\FunctionalPpaVerificationSheetPdfGenerator;
use App\Modules\InvoiceVerification\Services\Implementations\NullLdapDirectorySynchronizer;
use Illuminate\Support\ServiceProvider;

class InvoiceVerificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DocumentCompiler::class, FunctionalDocumentCompiler::class);
        $this->app->bind(PpaVerificationSheetPdfGenerator::class, FunctionalPpaVerificationSheetPdfGenerator::class);
        $this->app->bind(LdapDirectorySynchronizer::class, NullLdapDirectorySynchronizer::class);
        $this->app->bind(EprocDataProviderInterface::class, function () {
            return match (config('invoice_verification.eproc.driver', 'local')) {
                'api' => app(ApiEprocDataProvider::class),
                'local' => app(LocalEprocDataProvider::class),
                default => throw new \InvalidArgumentException('Driver eProc tidak dikenali.'),
            };
        });
    }
}
