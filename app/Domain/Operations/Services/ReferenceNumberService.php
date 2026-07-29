<?php

declare(strict_types=1);

namespace App\Domain\Operations\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class ReferenceNumberService
{
    /**
     * @param  class-string<Model>  $model
     */
    public function generate(string $model, string $column, string $prefix): string
    {
        do {
            $reference = sprintf(
                '%s-%s-%s',
                $prefix,
                now()->format('ymd'),
                Str::upper(Str::random(6)),
            );
        } while ($model::query()->where($column, $reference)->exists());

        return $reference;
    }
}
