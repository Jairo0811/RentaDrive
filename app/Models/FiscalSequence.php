<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FiscalSequence extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'document_type',
        'prefix',
        'next_number',
        'end_number',
        'sequence_length',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
            'end_number' => 'integer',
            'sequence_length' => 'integer',
            'expires_at' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
