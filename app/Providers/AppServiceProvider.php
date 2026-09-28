<?php

namespace App\Providers;

use App\Support\Tenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use App\Support\Tenancy\TenantModelRegistry;
use App\Support\Tenancy\TenantResolver;
use App\Support\Tenancy\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('public-read', function (Request $request): Limit {
            $company = $request->route('company');
            $companyKey = $company instanceof \App\Models\Company
                ? (string) $company->getKey()
                : (string) $company;

            return Limit::perMinute(120)
                ->by('public-read:'.$request->ip().':'.$companyKey);
        });

        RateLimiter::for('public-write', function (Request $request): Limit {
            $company = $request->route('company');
            $companyKey = $company instanceof \App\Models\Company
                ? (string) $company->getKey()
                : (string) $company;

            return Limit::perMinute(10)
                ->by('public-write:'.$request->ip().':'.$companyKey);
        });

        RateLimiter::for('tenant', fn (Request $request): Limit => Limit::perMinute(300)
            ->by('tenant:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));

        RateLimiter::for('platform', fn (Request $request): Limit => Limit::perMinute(180)
            ->by('platform:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));

        RateLimiter::for('webhook', fn (Request $request): Limit => Limit::perMinute(240)
            ->by('webhook:'.$request->ip()));

        RateLimiter::for('health', fn (Request $request): Limit => Limit::perMinute(60)
            ->by('health:'.$request->ip()));

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
