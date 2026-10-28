<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FiscalProfile extends Model
{
    use Auditable;

    protected $fillable = ['company_id', 'legal_name', 'rnc', 'address', 'fiscal_mode'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
