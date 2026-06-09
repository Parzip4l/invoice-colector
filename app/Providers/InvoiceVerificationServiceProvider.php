<?php

namespace App\Providers;

use App\Modules\InvoiceVerification\Services\Contracts\DocumentCompiler;
use App\Modules\InvoiceVerification\Services\Contracts\LdapDirectorySynchronizer;
use App\Modules\InvoiceVerification\Services\Contracts\PpaVerificationSheetPdfGenerator;
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
    }
}
