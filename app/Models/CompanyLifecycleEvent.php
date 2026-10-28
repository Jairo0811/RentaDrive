<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CompanyLifecycleEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'actor_id', 'previous_status', 'new_status',
        'previous_plan', 'new_plan', 'reason', 'created_at',
    ];

    protected function casts(): array
    {
        return ['company_id' => 'integer', 'actor_id' => 'integer', 'created_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
