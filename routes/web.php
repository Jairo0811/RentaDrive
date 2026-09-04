<?php

use App\Domain\Security\Enums\PermissionName;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FleetCatalogController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleMaintenanceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::get('/dashboard', DashboardController::class)
    ->middleware([
        'auth',
        'tenant',
        'verified',
        'permission:'.PermissionName::VIEW_DASHBOARD->value,
    ])
    ->name('dashboard');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('customers', CustomerController::class)
        ->middlewareFor(['index', 'show'], 'permission:'.PermissionName::VIEW_CUSTOMERS->value)
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'permission:'.PermissionName::MANAGE_CUSTOMERS->value);

    Route::get('/fleet/catalogs', [FleetCatalogController::class, 'index'])
        ->middleware('permission:'.PermissionName::VIEW_VEHICLES->value)
        ->name('fleet.catalogs');

    Route::middleware('permission:'.PermissionName::MANAGE_VEHICLES->value)->group(function (): void {
        Route::post('/fleet/brands', [FleetCatalogController::class, 'storeBrand'])->name('fleet.brands.store');
        Route::put('/fleet/brands/{brand}', [FleetCatalogController::class, 'updateBrand'])->name('fleet.brands.update');
        Route::delete('/fleet/brands/{brand}', [FleetCatalogController::class, 'destroyBrand'])->name('fleet.brands.destroy');
        Route::post('/fleet/categories', [FleetCatalogController::class, 'storeCategory'])->name('fleet.categories.store');
        Route::put('/fleet/categories/{category}', [FleetCatalogController::class, 'updateCategory'])->name('fleet.categories.update');
        Route::delete('/fleet/categories/{category}', [FleetCatalogController::class, 'destroyCategory'])->name('fleet.categories.destroy');
        Route::post('/fleet/models', [FleetCatalogController::class, 'storeModel'])->name('fleet.models.store');
        Route::put('/fleet/models/{model}', [FleetCatalogController::class, 'updateModel'])->name('fleet.models.update');
        Route::delete('/fleet/models/{model}', [FleetCatalogController::class, 'destroyModel'])->name('fleet.models.destroy');
        Route::post('/maintenances', [VehicleMaintenanceController::class, 'store'])->name('maintenances.store');
        Route::put('/maintenances/{maintenance}', [VehicleMaintenanceController::class, 'update'])->name('maintenances.update');
        Route::delete('/maintenances/{maintenance}', [VehicleMaintenanceController::class, 'destroy'])->name('maintenances.destroy');
    });

    Route::resource('vehicles', VehicleController::class)
        ->middlewareFor(['index', 'show'], 'permission:'.PermissionName::VIEW_VEHICLES->value)
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'permission:'.PermissionName::MANAGE_VEHICLES->value);

    Route::resource('reservations', ReservationController::class)
        ->middlewareFor(['index', 'show'], 'permission:'.PermissionName::VIEW_RESERVATIONS->value)
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'permission:'.PermissionName::MANAGE_RESERVATIONS->value);

    Route::get('/rentals/{rental}/contract', [RentalController::class, 'contract'])
        ->middleware('permission:'.PermissionName::MANAGE_CONTRACTS->value)
        ->name('rentals.contract');
    Route::patch('/rentals/{rental}/close', [RentalController::class, 'close'])
        ->middleware('permission:'.PermissionName::MANAGE_RETURNS->value)
        ->name('rentals.close');
    Route::resource('rentals', RentalController::class)->only(['index', 'create', 'store', 'show'])
        ->middlewareFor(['index', 'show'], 'permission:'.PermissionName::VIEW_RENTALS->value)
        ->middlewareFor(['create', 'store'], 'permission:'.PermissionName::MANAGE_RENTALS->value);

    Route::resource('inspections', InspectionController::class)->only(['index', 'create', 'store', 'show', 'destroy'])
        ->middleware('permission:'.PermissionName::MANAGE_INSPECTIONS->value);

    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])
        ->middleware('permission:'.PermissionName::VIEW_INVOICES->value)
        ->name('invoices.download');
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show', 'update'])
        ->middlewareFor(['index', 'show'], 'permission:'.PermissionName::VIEW_INVOICES->value)
        ->middlewareFor(['update'], 'permission:'.PermissionName::MANAGE_INVOICES->value);

    Route::resource('payments', PaymentController::class)->only(['index', 'store', 'destroy'])
        ->middlewareFor(['index'], 'permission:'.PermissionName::VIEW_PAYMENTS->value)
        ->middlewareFor(['store', 'destroy'], 'permission:'.PermissionName::MANAGE_PAYMENTS->value);

    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('permission:'.PermissionName::VIEW_REPORTS->value)
        ->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])
        ->middleware('permission:'.PermissionName::VIEW_REPORTS->value)
        ->name('reports.export');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])
        ->middleware('permission:'.PermissionName::VIEW_REPORTS->value)
        ->name('reports.export.pdf');

    Route::resource('users', UserController::class)->except(['show'])
        ->middleware('permission:'.PermissionName::MANAGE_USERS->value);

    Route::get('/settings', [SettingController::class, 'edit'])
        ->middleware('permission:'.PermissionName::MANAGE_CONFIGURATION->value)
        ->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])
        ->middleware('permission:'.PermissionName::MANAGE_CONFIGURATION->value)
        ->name('settings.update');

    Route::get('/audit', [AuditLogController::class, 'index'])
        ->middleware('permission:'.PermissionName::VIEW_AUDIT_LOG->value)
        ->name('audit.index');
});

require __DIR__.'/auth.php';
