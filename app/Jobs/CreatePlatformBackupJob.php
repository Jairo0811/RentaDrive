<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Support\Production\BackupManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CreatePlatformBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function backoff(): array
    {
        return [300, 1800];
    }

    public function handle(BackupManager $backups): void
    {
        $backups->create();
    }
}
