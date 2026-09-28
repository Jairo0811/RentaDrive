<?php

use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\RequestId;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SecurityHeaders;
use App\Jobs\CreatePlatformBackupJob;
use App\Jobs\EnforceSubscriptionLifecycleJob;
use App\Jobs\MonitorPlatformHealthJob;
use App\Jobs\ScanOperationalAlertsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->job(new ScanOperationalAlertsJob)
            ->everyFifteenMinutes()
            ->withoutOverlapping(10)
            ->onOneServer();

        $schedule->job(new EnforceSubscriptionLifecycleJob)
            ->hourly()
            ->withoutOverlapping(10)
            ->onOneServer();

        $schedule->job(new CreatePlatformBackupJob)
            ->dailyAt('02:30')
            ->onQueue('maintenance')
            ->withoutOverlapping(120)
            ->onOneServer();

        $schedule->job(new MonitorPlatformHealthJob)
            ->everyFiveMinutes()
            ->onQueue('maintenance')
            ->withoutOverlapping(5)
            ->onOneServer();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(RequestId::class);
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'tenant' => ResolveTenant::class,
            'platform_admin' => EnsurePlatformAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
