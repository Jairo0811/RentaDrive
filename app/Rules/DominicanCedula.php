<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class DominicanCedula implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cedula = preg_replace('/\D+/', '', (string) $value);

        if (strlen($cedula) !== 11) {
            $fail('La cédula debe contener 11 dígitos.');

            return;
        }

        $multipliers = [1, 2, 1, 2, 1, 2, 1, 2, 1, 2];
        $sum = 0;

        for ($index = 0; $index < 10; $index++) {
            $product = ((int) $cedula[$index]) * $multipliers[$index];
            $sum += $product >= 10 ? intdiv($product, 10) + ($product % 10) : $product;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        if ($checkDigit !== (int) $cedula[10]) {
            $fail('La cédula dominicana no es válida.');
        }
    }
}
