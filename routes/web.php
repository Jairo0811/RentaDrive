<?php

use App\Domain\Security\Enums\PermissionName;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FleetCatalogController;
use App\Http\Controllers\FleetScheduleController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Platform\PlatformBranchController;
use App\Http\Controllers\Platform\PlatformCompanyController;
use App\Http\Controllers\Platform\PlatformDashboardController;
use App\Http\Controllers\Platform\PlatformSubscriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\PublicBookingController;
use App\Http\Controllers\Public\PublicPaymentController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\RentalSignatureController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleMaintenanceController;
use Illuminate\Support\Facades\Route;

Route::get('/health/live', [HealthController::class, 'live'])
    ->middleware('throttle:health')
    ->name('health.live');
Route::get('/health/ready', [HealthController::class, 'ready'])
    ->middleware('throttle:health')
    ->name('health.ready');

Route::get('/', HomeController::class)->middleware('throttle:public-read')->name('home');

Route::prefix('r/{company:slug}')
    ->name('public.booking.')
    ->middleware('throttle:public-read')
    ->group(function (): void {
        Route::get('/', [PublicBookingController::class, 'show'])->name('show');
        Route::get('/search', [PublicBookingController::class, 'search'])->name('search');
        Route::get('/reserve', [PublicBookingController::class, 'create'])->name('create');
        Route::post('/reserve', [PublicBookingController::class, 'store'])
            ->middleware('throttle:public-write')
            ->name('store');
        Route::get('/reservation/{code}/deposit', [PublicPaymentController::class, 'show'])
            ->middleware('signed')
            ->name('deposit.show');
        Route::post('/reservation/{code}/deposit', [PublicPaymentController::class, 'start'])
            ->middleware(['signed', 'throttle:public-write'])
            ->name('deposit.start');
        Route::get('/reservation/{code}/cancel', [PublicBookingController::class, 'cancelShow'])
            ->middleware('signed')
            ->name('cancel.show');
        Route::post('/reservation/{code}/cancel', [PublicBookingController::class, 'cancelStore'])
            ->middleware(['signed', 'throttle:public-write'])
            ->name('cancel.store');
        Route::get('/reservation/{code}', [PublicBookingController::class, 'confirmation'])
            ->name('confirmation');
    });

Route::middleware(['auth'])->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'platform_admin', 'throttle:platform'])
    ->prefix('platform')
    ->name('platform.')
    ->group(function (): void {
        Route::get('/', PlatformDashboardController::class)->name('dashboard');

        Route::get('/companies', [PlatformCompanyController::class, 'index'])->name('companies.index');
        Route::get('/companies/create', [PlatformCompanyController::class, 'create'])->name('companies.create');
        Route::post('/companies', [PlatformCompanyController::class, 'store'])->name('companies.store');
        Route::get('/companies/{company}/edit', [PlatformCompanyController::class, 'edit'])->name('companies.edit');
        Route::put('/companies/{company}', [PlatformCompanyController::class, 'update'])->name('companies.update');
        Route::patch('/companies/{company}/suspend', [PlatformCompanyController::class, 'suspend'])->name('companies.suspend');
        Route::patch('/companies/{company}/activate', [PlatformCompanyController::class, 'activate'])->name('companies.activate');

        Route::post('/companies/{company}/subscription/activate', [PlatformSubscriptionController::class, 'activate'])->name('companies.subscription.activate');
        Route::post('/companies/{company}/subscription/past-due', [PlatformSubscriptionController::class, 'pastDue'])->name('companies.subscription.past-due');
        Route::delete('/companies/{company}/subscription', [PlatformSubscriptionController::class, 'cancel'])->name('companies.subscription.cancel');

        Route::get('/companies/{company}/branches', [PlatformBranchController::class, 'index'])->name('companies.branches.index');
        Route::get('/companies/{company}/branches/create', [PlatformBranchController::class, 'create'])->name('companies.branches.create');
        Route::post('/companies/{company}/branches', [PlatformBranchController::class, 'store'])->name('companies.branches.store');
        Route::get('/companies/{company}/branches/{branch}/edit', [PlatformBranchController::class, 'edit'])->name('companies.branches.edit');
        Route::put('/companies/{company}/branches/{branch}', [PlatformBranchController::class, 'update'])->name('companies.branches.update');
        Route::patch('/companies/{company}/branches/{branch}/status', [PlatformBranchController::class, 'toggleStatus'])->name('companies.branches.status');
        Route::delete('/companies/{company}/branches/{branch}', [PlatformBranchController::class, 'destroy'])->name('companies.branches.destroy');
    });

Route::get('/dashboard', DashboardController::class)
    ->middleware([
        'auth',
        'tenant',
        'verified',
        'permission:'.PermissionName::VIEW_DASHBOARD->value,
        'throttle:tenant',
    ])
    ->name('dashboard');

Route::middleware(['auth', 'tenant', 'throttle:tenant'])->group(function (): void {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');

    Route::resource('customers', CustomerController::class)
        ->middlewareFor(['index', 'show'], 'permission:'.PermissionName::VIEW_CUSTOMERS->value)
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'permission:'.PermissionName::MANAGE_CUSTOMERS->value);

    Route::get('/fleet/catalogs', [FleetCatalogController::class, 'index'])
        ->middleware('permission:'.PermissionName::VIEW_VEHICLES->value)
        ->name('fleet.catalogs');

    Route::get('/fleet/schedule', FleetScheduleController::class)
        ->middleware('permission:'.PermissionName::VIEW_VEHICLES->value)
        ->name('fleet.schedule');

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

    Route::get('/rentals/{rental}/signature', [RentalSignatureController::class, 'create'])
        ->middleware('permission:'.PermissionName::MANAGE_CONTRACTS->value)
        ->name('rentals.signature.create');
    Route::post('/rentals/{rental}/signature', [RentalSignatureController::class, 'store'])
        ->middleware('permission:'.PermissionName::MANAGE_CONTRACTS->value)
        ->name('rentals.signature.store');
    Route::get('/rentals/{rental}/check-in', [InspectionController::class, 'mobileDelivery'])
        ->middleware('permission:'.PermissionName::MANAGE_DELIVERIES->value)
        ->name('rentals.check-in');
    Route::get('/rentals/{rental}/check-out', [InspectionController::class, 'mobileReturn'])
        ->middleware('permission:'.PermissionName::MANAGE_RETURNS->value)
        ->name('rentals.check-out');

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
    Route::post('/invoices/{invoice}/fiscal', [InvoiceController::class, 'issueFiscal'])
        ->middleware('permission:'.PermissionName::MANAGE_INVOICES->value)
        ->name('invoices.fiscal.issue');
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show', 'update'])
        ->middlewareFor(['index', 'show'], 'permission:'.PermissionName::VIEW_INVOICES->value)
        ->middlewareFor(['update'], 'permission:'.PermissionName::MANAGE_INVOICES->value);

    Route::post('/payments/reconcile', [PaymentController::class, 'reconcile'])
        ->middleware('permission:'.PermissionName::MANAGE_PAYMENTS->value)
        ->name('payments.reconcile');
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund'])
        ->middleware('permission:'.PermissionName::MANAGE_PAYMENTS->value)
        ->name('payments.refund');
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
