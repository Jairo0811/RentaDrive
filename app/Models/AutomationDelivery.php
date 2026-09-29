<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AutomationDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_key',
        'channel',
        'subject_type',
        'subject_id',
        'recipient',
        'title',
        'message',
        'status',
        'attempts',
        'sent_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'subject_id' => 'integer',
            'attempts' => 'integer',
            'sent_at' => 'datetime',
        ];
    }
}
