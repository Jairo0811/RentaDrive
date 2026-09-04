<?php

namespace App\Providers;

use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantModelRegistry;
use App\Support\Tenancy\TenantResolver;
use App\Support\Tenancy\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(TenantResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            ConnectionEstablished::class,
            static function (ConnectionEstablished $event): void {
                if ($event->connection->getDriverName() === 'sqlsrv') {
                    $event->connection->unprepared('SET DATEFORMAT ymd');
                }
            }
        );

        $resolver = app(TenantResolver::class);
        $scope = new TenantScope($resolver);
        $branchModels = TenantModelRegistry::branchModels();

        foreach (TenantModelRegistry::models() as $modelClass) {
            $modelClass::addGlobalScope($scope);

            $modelClass::creating(static function (Model $model) use ($resolver, $branchModels): void {
                if ($model->getAttribute('company_id') === null && ($companyId = $resolver->companyId()) !== null) {
                    $model->setAttribute('company_id', $companyId);
                }

                if (
                    in_array($model::class, $branchModels, true)
                    && $model->getAttribute('branch_id') === null
                    && ($branchId = $resolver->branchId()) !== null
                ) {
                    $model->setAttribute('branch_id', $branchId);
                }
            });
        }
    }
}
