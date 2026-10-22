<?php

use App\Modules\InvoiceVerification\Http\Controllers\Api\MobileInvoiceCollectorAuthController;
use App\Modules\InvoiceVerification\Http\Controllers\Api\MobileInvoiceCollectorController;
use App\Modules\InvoiceVerification\Http\Controllers\Api\OrganizationDirectoryController;
use App\Modules\InvoiceVerification\Http\Controllers\Api\PortalApprovalController;
use Illuminate\Support\Facades\Route;

Route::prefix('invoice-verification')
    ->as('api.invoice-verification.')
    ->group(function () {
        Route::get('/organization/divisions', [OrganizationDirectoryController::class, 'divisions'])->name('organization.divisions');
        Route::get('/organization/departments', [OrganizationDirectoryController::class, 'departments'])->name('organization.departments');
        Route::get('/organization/tree', [OrganizationDirectoryController::class, 'tree'])->name('organization.tree');
    });

Route::prefix('portal/v1/invoice-collector')
    ->middleware('throttle:120,1')
    ->group(function () {
        Route::get('/approvals', [PortalApprovalController::class, 'index']);
        Route::get('/approvals/{approvalTransaction}', [PortalApprovalController::class, 'show']);
        Route::post('/approvals/{approvalTransaction}/decision', [PortalApprovalController::class, 'decision']);
    });

Route::prefix('mobile/v1/invoice-collector')->group(function () {
    Route::post('/auth/sso-exchange', [MobileInvoiceCollectorAuthController::class, 'exchange'])
        ->middleware('throttle:30,1');

    Route::middleware(['invoice.mobile', 'throttle:120,1'])->group(function () {
        Route::post('/auth/logout', [MobileInvoiceCollectorAuthController::class, 'logout']);
        Route::get('/me', [MobileInvoiceCollectorController::class, 'me']);
        Route::get('/transactions', [MobileInvoiceCollectorController::class, 'index']);
        Route::get('/transactions/{transaction}', [MobileInvoiceCollectorController::class, 'show']);
        Route::get('/transactions/{transaction}/history', [MobileInvoiceCollectorController::class, 'history']);
        Route::get('/transactions/{transaction}/documents/{document}', [MobileInvoiceCollectorController::class, 'document'])
            ->name('api.mobile.invoice-collector.documents.show');
    });
});
