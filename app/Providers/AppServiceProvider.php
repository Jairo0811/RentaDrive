<?php

namespace App\Providers;

use App\Support\Tenancy\TenantContext;
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
    }
}
