<?php

declare(strict_types=1);

namespace App\Support\Production;

use App\Models\BackupSnapshot;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class BackupManager
{
    public function create(?int $initiatedBy = null): BackupSnapshot
    {
        $disk = (string) config('rentadrive.storage.backup_disk', 'local');
        $this->assertDiskPolicy($disk);

        $snapshot = BackupSnapshot::query()->create([
            'disk' => $disk,
            'path' => 'pending',
            'status' => 'running',
            'started_at' => now(),
            'initiated_by' => $initiatedBy,
        ]);

        try {
            $payload = [
                'format' => 'rentadrive-portable-backup-v1',
                'created_at' => now()->toIso8601String(),
                'database' => (string) config('database.default'),
                'tables' => [],
            ];

            foreach ($this->tables() as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $payload['tables'][$table] = DB::table($table)
                    ->orderBy($this->orderColumn($table))
                    ->get()
                    ->map(fn (object $row): array => (array) $row)
                    ->all();
            }

            $json = json_encode(
                $payload,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );

            $compressed = gzencode($json, 9);

            if ($compressed === false) {
                throw new RuntimeException('No fue posible comprimir el backup.');
            }

            $encrypted = Crypt::encryptString(base64_encode($compressed));
            $path = 'backups/'.now()->format('Y/m').'/rentadrive-'.now()->format('Ymd-His').'-'.Str::uuid().'.rdbackup';

            Storage::disk($disk)->put($path, $encrypted);

            $snapshot->update([
                'path' => $path,
                'status' => 'completed',
                'byte_size' => strlen($encrypted),
                'checksum_sha256' => hash('sha256', $encrypted),
                'completed_at' => now(),
                'metadata' => [
                    'tables' => collect($payload['tables'])
                        ->map(fn (array $rows): int => count($rows))
                        ->all(),
                ],
            ]);

            $this->verify($snapshot->fresh());
            $this->prune();

            return $snapshot->fresh();
        } catch (Throwable $exception) {
            $snapshot->update([
                'status' => 'failed',
                'error' => mb_substr($exception->getMessage(), 0, 4000),
                'completed_at' => now(),
            ]);

            throw $exception;
        }
    }

    public function verify(BackupSnapshot $snapshot): bool
    {
        if ($snapshot->status !== 'completed') {
            throw new RuntimeException('Solo se pueden verificar backups completados.');
        }

        $disk = Storage::disk($snapshot->disk);

        if (! $disk->exists($snapshot->path)) {
            throw new RuntimeException('El archivo de backup no existe en el disco configurado.');
        }

        $encrypted = $disk->get($snapshot->path);
        $checksum = hash('sha256', $encrypted);

        if (! hash_equals((string) $snapshot->checksum_sha256, $checksum)) {
            throw new RuntimeException('El checksum del backup no coincide.');
        }

        $payload = $this->decode($encrypted);

        if (($payload['format'] ?? null) !== 'rentadrive-portable-backup-v1') {
            throw new RuntimeException('El formato del backup no es compatible.');
        }

        $snapshot->update(['verified_at' => now()]);

        return true;
    }

    public function restore(BackupSnapshot $snapshot): void
    {
        $this->verify($snapshot);

        $encrypted = Storage::disk($snapshot->disk)->get($snapshot->path);
        $payload = $this->decode($encrypted);
        $tables = (array) ($payload['tables'] ?? []);

        DB::transaction(function () use ($tables): void {
            Schema::disableForeignKeyConstraints();

            try {
                foreach (array_reverse($this->tables()) as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }

                foreach ($this->tables() as $table) {
                    if (! isset($tables[$table]) || ! is_array($tables[$table]) || ! Schema::hasTable($table)) {
                        continue;
                    }

                    $rows = $tables[$table];

                    if ($rows === []) {
                        continue;
                    }

                    $identityInsert = DB::getDriverName() === 'sqlsrv' && Schema::hasColumn($table, 'id');

                    if ($identityInsert) {
                        DB::statement('SET IDENTITY_INSERT ['.$table.'] ON');
                    }

                    try {
                        foreach (array_chunk($rows, 100) as $chunk) {
                            DB::table($table)->insert($chunk);
                        }
                    } finally {
                        if ($identityInsert) {
                            DB::statement('SET IDENTITY_INSERT ['.$table.'] OFF');
                        }
                    }
                }
            } finally {
                Schema::enableForeignKeyConstraints();
            }
        });
    }

    public function latestHealthy(): ?BackupSnapshot
    {
        return BackupSnapshot::query()
            ->where('status', 'completed')
            ->whereNotNull('verified_at')
            ->latest('completed_at')
            ->first();
    }

    public function prune(): void
    {
        $retentionDays = max(1, (int) config('rentadrive.backup.retention_days', 14));
        $cutoff = now()->subDays($retentionDays);

        BackupSnapshot::query()
            ->where('status', 'completed')
            ->where('completed_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(50, function ($snapshots): void {
                foreach ($snapshots as $snapshot) {
                    Storage::disk($snapshot->disk)->delete($snapshot->path);
                    $snapshot->delete();
                }
            });
    }

    /**
     * @return list<string>
     */
    private function tables(): array
    {
        return array_values(array_filter(
            (array) config('rentadrive.backup.tables', []),
            fn (mixed $table): bool => is_string($table) && $table !== '',
        ));
    }

    private function orderColumn(string $table): string
    {
        return Schema::hasColumn($table, 'id') ? 'id' : $this->firstColumn($table);
    }

    private function firstColumn(string $table): string
    {
        $columns = Schema::getColumnListing($table);

        return $columns[0] ?? throw new RuntimeException('La tabla '.$table.' no tiene columnas.');
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $encrypted): array
    {
        $compressedBase64 = Crypt::decryptString($encrypted);
        $compressed = base64_decode($compressedBase64, true);

        if ($compressed === false) {
            throw new RuntimeException('El contenido cifrado del backup no es válido.');
        }

        $json = gzdecode($compressed);

        if ($json === false) {
            throw new RuntimeException('No fue posible descomprimir el backup.');
        }

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new RuntimeException('El backup no contiene un payload válido.');
        }

        return $decoded;
    }

    private function assertDiskPolicy(string $disk): void
    {
        if (
            app()->environment('production')
            && (bool) config('rentadrive.backup.require_external_in_production', true)
            && in_array($disk, ['local', 'public'], true)
        ) {
            throw new RuntimeException(
                'En producción RENTADRIVE_BACKUP_DISK debe apuntar a almacenamiento externo.',
            );
        }
    }
}
