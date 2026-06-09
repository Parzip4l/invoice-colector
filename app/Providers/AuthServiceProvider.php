<?php

namespace App\Providers;

use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;
use App\Modules\InvoiceVerification\Domain\Models\GeneratedDocument;
use App\Modules\InvoiceVerification\Domain\Models\PpaVerificationSheet;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionDocument;
use App\Modules\InvoiceVerification\Policies\ApprovalTransactionPolicy;
use App\Modules\InvoiceVerification\Policies\GeneratedDocumentPolicy;
use App\Modules\InvoiceVerification\Policies\PpaVerificationSheetPolicy;
use App\Modules\InvoiceVerification\Policies\TransactionDocumentPolicy;
use App\Modules\InvoiceVerification\Policies\TransactionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Transaction::class => TransactionPolicy::class,
        ApprovalTransaction::class => ApprovalTransactionPolicy::class,
        GeneratedDocument::class => GeneratedDocumentPolicy::class,
        TransactionDocument::class => TransactionDocumentPolicy::class,
        PpaVerificationSheet::class => PpaVerificationSheetPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
