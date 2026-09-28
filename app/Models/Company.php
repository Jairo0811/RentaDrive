<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

final class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'legal_name',
        'rnc',
        'slug',
        'email',
        'phone',
        'currency',
        'timezone',
        'status',
        'plan_code',
        'trial_ends_at',
        'public_domain',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->settings ?? [], $key, $default);
    }

    public function brandLogoUrl(): ?string
    {
        $path = $this->setting('branding.logo_path');

        return is_string($path) && $path !== ''
            ? Storage::disk('public')->url($path)
            : null;
    }
}
