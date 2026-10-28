<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AutomationDelivery extends Model
{
    protected $fillable = [
        'company_id', 'event_key', 'recipient', 'channel',
        'status', 'sent_at', 'last_error',
    ];

    protected function casts(): array
    {
        return ['company_id' => 'integer', 'sent_at' => 'datetime'];
    }
}
