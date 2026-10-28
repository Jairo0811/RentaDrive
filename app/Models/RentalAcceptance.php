<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RentalAcceptance extends Model
{
    protected $fillable = [
        'company_id', 'rental_id', 'recorded_by', 'signer_name', 'signer_role',
        'document_hash', 'ip_address', 'user_agent', 'accepted_at',
    ];

    protected function casts(): array
    {
        return ['company_id' => 'integer', 'accepted_at' => 'datetime'];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
