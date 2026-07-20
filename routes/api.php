<?php

use App\Modules\InvoiceVerification\Http\Controllers\Api\OrganizationDirectoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('invoice-verification')
    ->as('api.invoice-verification.')
    ->group(function () {
        Route::get('/organization/divisions', [OrganizationDirectoryController::class, 'divisions'])->name('organization.divisions');
        Route::get('/organization/departments', [OrganizationDirectoryController::class, 'departments'])->name('organization.departments');
        Route::get('/organization/tree', [OrganizationDirectoryController::class, 'tree'])->name('organization.tree');
    });
