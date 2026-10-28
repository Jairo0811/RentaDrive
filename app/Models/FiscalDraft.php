<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FiscalDraft extends Model
{
    use Auditable;

    protected $fillable = [
        'company_id', 'invoice_id', 'kind', 'status', 'issuer_rnc',
        'buyer_document', 'subtotal', 'tax', 'total', 'snapshot',
    ];

    protected function casts(): array
    {
        return ['company_id' => 'integer', 'subtotal' => 'decimal:2', 'tax' => 'decimal:2', 'total' => 'decimal:2', 'snapshot' => 'array'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
