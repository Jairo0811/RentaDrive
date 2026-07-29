<?php

declare(strict_types=1);

namespace App\Domain\Operations\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

final class SettingValue
{
    public static function get(string $key, ?string $default = null): ?string
    {
        if (! Schema::hasTable('settings')) {
            return $default;
        }

        return Setting::query()->where('key', $key)->value('value') ?? $default;
    }
}
