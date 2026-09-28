<?php

declare(strict_types=1);

namespace App\Support\Production;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class PlatformHealthService
{
    /**
     * @return array{healthy:bool,checks:array<string,array{healthy:bool,detail:string}>}
     */
    public function check(): array
    {
        $checks = [
            'database' => $this->probe(function (): string {
                DB::select('SELECT 1 AS health_check');

                return 'reachable';
            }),
            'cache' => $this->probe(function (): string {
                $key = 'health:'.Str::uuid();
                Cache::put($key, 'ok', 30);
                $value = Cache::pull($key);

                if ($value !== 'ok') {
                    throw new \RuntimeException('Cache round-trip failed.');
                }

                return 'round-trip';
            }),
            'private_storage' => $this->probe(function (): string {
                $diskName = (string) config('rentadrive.storage.private_disk', 'local');
                $disk = Storage::disk($diskName);
                $path = 'health/.probe-'.Str::uuid();
                $disk->put($path, 'ok');

                try {
                    if ($disk->get($path) !== 'ok') {
                        throw new \RuntimeException('Storage round-trip failed.');
                    }
                } finally {
                    $disk->delete($path);
                }

                return $diskName;
            }),
            'queue' => $this->probe(function (): string {
                if (! Schema::hasTable('jobs') || ! Schema::hasTable('failed_jobs')) {
                    throw new \RuntimeException('Queue tables are missing.');
                }

                $pending = DB::table('jobs')->count();
                $failed = DB::table('failed_jobs')->count();

                if ($failed > (int) config('rentadrive.health.max_failed_jobs', 25)) {
                    throw new \RuntimeException('Too many failed jobs: '.$failed);
                }

                return 'pending='.$pending.', failed='.$failed;
            }),
            'backup' => $this->probe(function (): string {
                if (! Schema::hasTable('backup_snapshots')) {
                    throw new \RuntimeException('Backup registry is missing.');
                }

                $latest = app(BackupManager::class)->latestHealthy();

                if ($latest === null) {
                    if (app()->environment('production')) {
                        throw new \RuntimeException('No verified backup exists.');
                    }

                    return 'not-created-yet';
                }

                $maxAgeHours = max(1, (int) config('rentadrive.health.max_backup_age_hours', 30));

                if ($latest->completed_at === null || $latest->completed_at->lt(now()->subHours($maxAgeHours))) {
                    throw new \RuntimeException('Latest verified backup is stale.');
                }

                return 'verified '.$latest->completed_at->toIso8601String();
            }),
        ];

        return [
            'healthy' => collect($checks)->every(fn (array $check): bool => $check['healthy']),
            'checks' => $checks,
        ];
    }

    /**
     * @param  callable(): string  $callback
     * @return array{healthy:bool,detail:string}
     */
    private function probe(callable $callback): array
    {
        try {
            return ['healthy' => true, 'detail' => $callback()];
        } catch (Throwable $exception) {
            return [
                'healthy' => false,
                'detail' => app()->environment('production')
                    ? 'unavailable'
                    : mb_substr($exception->getMessage(), 0, 300),
            ];
        }
    }
}
