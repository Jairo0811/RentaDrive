<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Rules\DominicanCedula;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class DominicanCedulaTest extends TestCase
{
    public function test_it_accepts_a_valid_dominican_cedula(): void
    {
        $validator = Validator::make(
            ['document_number' => '00113918205'],
            ['document_number' => [new DominicanCedula]],
        );

        $this->assertTrue($validator->passes());
    }

    public function test_it_rejects_an_invalid_dominican_cedula(): void
    {
        $validator = Validator::make(
            ['document_number' => '00113918206'],
            ['document_number' => [new DominicanCedula]],
        );

        $this->assertTrue($validator->fails());
    }
}
