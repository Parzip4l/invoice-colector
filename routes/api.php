<?php

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
