<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BackupSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'disk',
        'path',
        'status',
        'byte_size',
        'checksum_sha256',
        'started_at',
        'completed_at',
        'verified_at',
        'error',
        'initiated_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'byte_size' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}
