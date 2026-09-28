<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RentalSignature extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'rental_id',
        'role',
        'signer_name',
        'signer_document',
        'signature_path',
        'contract_hash',
        'accepted_terms_at',
        'signed_at',
        'ip_hash',
        'user_agent_hash',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'accepted_terms_at' => 'datetime',
            'signed_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'ip_hash',
        'user_agent_hash',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
