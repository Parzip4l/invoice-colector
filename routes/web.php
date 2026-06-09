<?php

use App\Http\Controllers\RoutingController;
use App\Modules\InvoiceVerification\Http\Controllers\AdminDocumentGenerationController;
use App\Modules\InvoiceVerification\Http\Controllers\AccountingVerificationController;
use App\Modules\InvoiceVerification\Http\Controllers\ApprovalController;
use App\Modules\InvoiceVerification\Http\Controllers\ArchiveController;
use App\Modules\InvoiceVerification\Http\Controllers\AuditLogController;
use App\Modules\InvoiceVerification\Http\Controllers\CompiledDocumentController;
use App\Modules\InvoiceVerification\Http\Controllers\DashboardController;
use App\Modules\InvoiceVerification\Http\Controllers\DocumentController;
use App\Modules\InvoiceVerification\Http\Controllers\DocumentFileController;
use App\Modules\InvoiceVerification\Http\Controllers\FinanceController;
use App\Modules\InvoiceVerification\Http\Controllers\GeneratedDocumentController;
use App\Modules\InvoiceVerification\Http\Controllers\MasterDataController;
use App\Modules\InvoiceVerification\Http\Controllers\NumberingRegisterController;
use App\Modules\InvoiceVerification\Http\Controllers\PpaVerificationSheetController;
use App\Modules\InvoiceVerification\Http\Controllers\TransactionController;
use App\Modules\InvoiceVerification\Http\Controllers\VendorReviewController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';

Route::group(['prefix' => '/', 'middleware' => 'auth'], function () {
    Route::get('', [DashboardController::class, 'index'])->name('root');
    Route::redirect('home', '/invoice-verification/dashboard')->name('home.redirect');

    Route::prefix('invoice-verification')
        ->as('invoice-verification.')
        ->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            Route::prefix('transactions')->as('transactions.')->group(function () {
                Route::get('/', [TransactionController::class, 'index'])->name('index');
                Route::get('/create', [TransactionController::class, 'create'])->name('create');
                Route::post('/', [TransactionController::class, 'store'])->name('store');
                Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
                Route::put('/{transaction}/invoice-metadata', [TransactionController::class, 'updateInvoiceMetadata'])->name('invoice-metadata.update');
                Route::get('/{transaction}/documents', [DocumentController::class, 'show'])->name('documents.show');
                Route::post('/{transaction}/documents/ppa', [DocumentController::class, 'storePpa'])->name('documents.ppa.store');
                Route::post('/{transaction}/documents/combined', [DocumentController::class, 'storeCombined'])->name('documents.combined.store');
                Route::post('/{transaction}/generate-admin-documents', [AdminDocumentGenerationController::class, 'store'])->name('admin-documents.generate');
                Route::get('/{transaction}/ppa-verification-sheet', [PpaVerificationSheetController::class, 'edit'])->name('ppa-verification-sheets.edit');
                Route::get('/{transaction}/ppa-verification-sheet/preview', [PpaVerificationSheetController::class, 'preview'])->name('ppa-verification-sheets.preview');
                Route::put('/{transaction}/ppa-verification-sheet', [PpaVerificationSheetController::class, 'update'])->name('ppa-verification-sheets.update');
                Route::post('/{transaction}/ppa-verification-sheet/submit', [PpaVerificationSheetController::class, 'submit'])->name('ppa-verification-sheets.submit');
                Route::post('/{transaction}/ppa-verification-sheet/decision', [PpaVerificationSheetController::class, 'decision'])->name('ppa-verification-sheets.decision');
                Route::get('/{transaction}/accounting-verification', [AccountingVerificationController::class, 'edit'])->name('accounting-verifications.edit');
                Route::put('/{transaction}/accounting-verification', [AccountingVerificationController::class, 'update'])->name('accounting-verifications.update');
            });

            Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
            Route::get('/kadep-review', [ApprovalController::class, 'index'])->name('kadep-review.index');
            Route::get('/kadiv-review', [ApprovalController::class, 'index'])->name('kadiv-review.index');
            Route::put('/approvals/{approvalTransaction}', [ApprovalController::class, 'update'])->name('approvals.update');

            Route::get('/generated-documents/{generatedDocument}', [GeneratedDocumentController::class, 'show'])->name('generated-documents.show');
            Route::get('/generated-documents/{generatedDocument}/preview', [GeneratedDocumentController::class, 'preview'])->name('generated-documents.preview');
            Route::get('/transaction-documents/{transactionDocument}/preview', [DocumentFileController::class, 'preview'])->name('transaction-documents.preview');
            Route::get('/vendor-reviews', [VendorReviewController::class, 'index'])->name('vendor-reviews.index');
            Route::put('/vendor-reviews/{transactionDocument}', [VendorReviewController::class, 'update'])->name('vendor-reviews.update');
            Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
            Route::put('/finance/{transaction}', [FinanceController::class, 'update'])->name('finance.update');
            Route::get('/numbering-registers', [NumberingRegisterController::class, 'index'])->name('numbering-registers.index');
            Route::put('/numbering-registers/{numberingRegister}', [NumberingRegisterController::class, 'update'])->name('numbering-registers.update');
            Route::get('/compiled-documents', [CompiledDocumentController::class, 'index'])->name('compiled-documents.index');
            Route::get('/compiled-documents/{compiledDocument}', [CompiledDocumentController::class, 'show'])->name('compiled-documents.show');
            Route::get('/archive', [ArchiveController::class, 'index'])->name('archive.index');
            Route::get('/master-data', [MasterDataController::class, 'index'])->name('master-data.index');
            Route::post('/master-data/banks', [MasterDataController::class, 'storeBank'])->name('master-data.banks.store');
            Route::post('/master-data/vendors', [MasterDataController::class, 'storeVendor'])->name('master-data.vendors.store');
            Route::post('/master-data/memo-requests', [MasterDataController::class, 'storeMemo'])->name('master-data.memo-requests.store');
            Route::get('/master-data/memo-requests/{memoRequest}/download', [MasterDataController::class, 'downloadMemo'])->name('master-data.memo-requests.download');
            Route::post('/master-data/agreement-references', [MasterDataController::class, 'storeAgreement'])->name('master-data.agreement-references.store');
            Route::get('/master-data/agreement-references/{agreementReference}/download', [MasterDataController::class, 'downloadAgreement'])->name('master-data.agreement-references.download');
            Route::post('/master-data/template-references', [MasterDataController::class, 'storeTemplate'])->name('master-data.template-references.store');
            Route::post('/master-data/ldap-sync', [MasterDataController::class, 'syncLdap'])->name('master-data.ldap-sync');
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        });

    Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])->name('third');
    Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])->name('second');
    Route::get('{any}', [RoutingController::class, 'root'])->name('any');
});
