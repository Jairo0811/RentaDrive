<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'plan_code',
        'status',
        'billing_cycle',
        'provider',
        'provider_reference',
        'started_at',
        'trial_ends_at',
        'current_period_starts_at',
        'current_period_ends_at',
        'grace_ends_at',
        'cancelled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isEntitled(): bool
    {
        return match ($this->status) {
            'active' => $this->current_period_ends_at === null || $this->current_period_ends_at->isFuture(),
            'trialing' => $this->trial_ends_at !== null && $this->trial_ends_at->isFuture(),
            'past_due' => $this->grace_ends_at !== null && $this->grace_ends_at->isFuture(),
            default => false,
        };
    }
}
