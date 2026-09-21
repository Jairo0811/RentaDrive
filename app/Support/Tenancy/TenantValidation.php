<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use LogicException;

final class TenantValidation
{
    public static function exists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)
            ->where('company_id', self::companyId());
    }

    public static function unique(string $table, string $column = 'NULL'): Unique
    {
        return Rule::unique($table, $column)
            ->where('company_id', self::companyId());
    }

    private static function companyId(): int
    {
        return app(TenantResolver::class)->companyId()
            ?? throw new LogicException('No hay una empresa activa para validar la operación.');
    }
}
