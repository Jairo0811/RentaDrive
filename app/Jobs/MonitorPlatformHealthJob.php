<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Support\Production\PlatformHealthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class MonitorPlatformHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(PlatformHealthService $health): void
    {
        $result = $health->check();

        if ($result['healthy']) {
            return;
        }

        Log::critical('RentaDrive readiness degraded.', [
            'checks' => $result['checks'],
        ]);

        $opsEmail = trim((string) config('rentadrive.health.ops_email'));

        if ($opsEmail === '') {
            return;
        }

        $fingerprint = hash('sha256', json_encode($result['checks'], JSON_THROW_ON_ERROR));
        $alertKey = 'rentadrive:health-alert:'.$fingerprint;

        if (! Cache::add($alertKey, true, now()->addMinutes(30))) {
            return;
        }

        $lines = collect($result['checks'])
            ->filter(fn (array $check): bool => ! $check['healthy'])
            ->map(fn (array $check, string $name): string => $name.': '.$check['detail'])
            ->implode(PHP_EOL);

        Mail::raw(
            "RentaDrive detectó un estado degradado:".PHP_EOL.PHP_EOL.$lines,
            fn ($message) => $message
                ->to($opsEmail)
                ->subject('RentaDrive · alerta de producción'),
        );
    }
}
