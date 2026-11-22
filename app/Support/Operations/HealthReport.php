<?php

declare(strict_types=1);

namespace App\Support\Operations;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Estado operativo: base de datos, cola y scheduler. Solo devuelve contadores y códigos de problema;
 * nunca hosts, credenciales, trazas ni mensajes de excepción.
 */
final class HealthReport
{
    public const HEARTBEAT_KEY = 'rentadrive:scheduler:heartbeat';

    public function databaseIsUp(): bool
    {
        try {
            DB::connection()->select('SELECT 1 AS ok');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array{status: string, checks: array<string, array<string, mixed>>, problems: list<string>}
     */
    public function details(): array
    {
        $checks = [
            'database' => $this->database(),
            'queue' => $this->queue(),
            'scheduler' => $this->scheduler(),
        ];

        $problems = [];

        foreach ($checks as $name => $check) {
            foreach ($check['problems'] as $problem) {
                $problems[] = $name.'.'.$problem;
            }
            unset($checks[$name]['problems']);
        }

        return [
            'status' => $problems === [] ? 'ok' : 'degraded',
            'checks' => $checks,
            'problems' => $problems,
        ];
    }

    /** @return array<string, mixed> */
    private function database(): array
    {
        $start = hrtime(true);
        $up = $this->databaseIsUp();

        return [
            'up' => $up,
            'latency_ms' => (int) round((hrtime(true) - $start) / 1_000_000),
            'problems' => $up ? [] : ['unreachable'],
        ];
    }

    /** @return array<string, mixed> */
    private function queue(): array
    {
        $connection = (string) config('queue.default');

        if ($connection !== 'database') {
            return ['driver' => $connection, 'monitored' => false, 'problems' => []];
        }

        try {
            $jobsTable = (string) config('queue.connections.database.table', 'jobs');
            $failedTable = (string) config('queue.failed.table', 'failed_jobs');
            $now = time();

            $pending = (int) DB::table($jobsTable)->whereNull('reserved_at')->count();
            $oldest = DB::table($jobsTable)->whereNull('reserved_at')->where('available_at', '<=', $now)->min('available_at');
            $failed = (int) DB::table($failedTable)->count();
        } catch (Throwable) {
            return ['driver' => $connection, 'monitored' => true, 'problems' => ['unreadable']];
        }

        $age = $oldest === null ? 0 : max(0, $now - (int) $oldest);
        $problems = [];

        if ($age > (int) config('rentadrive.operations.max_queue_age_seconds')) {
            $problems[] = 'stalled';
        }

        if ($failed > (int) config('rentadrive.operations.max_failed_jobs')) {
            $problems[] = 'failed_jobs';
        }

        return [
            'driver' => $connection,
            'monitored' => true,
            'pending' => $pending,
            'oldest_pending_age_seconds' => $age,
            'failed' => $failed,
            'problems' => $problems,
        ];
    }

    /** @return array<string, mixed> */
    private function scheduler(): array
    {
        try {
            $heartbeat = Cache::get(self::HEARTBEAT_KEY);
        } catch (Throwable) {
            $heartbeat = null;
        }

        $age = is_numeric($heartbeat) ? max(0, time() - (int) $heartbeat) : null;
        $required = (bool) config('rentadrive.operations.require_scheduler');
        $problems = [];

        if ($required && ($age === null || $age > (int) config('rentadrive.operations.max_scheduler_age_seconds'))) {
            $problems[] = 'stale';
        }

        return [
            'required' => $required,
            'last_heartbeat_age_seconds' => $age,
            'problems' => $problems,
        ];
    }
}
