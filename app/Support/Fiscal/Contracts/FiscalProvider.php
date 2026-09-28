<?php

declare(strict_types=1);

namespace App\Support\Fiscal\Contracts;

interface FiscalProvider
{
    public function name(): string;

    /**
     * @param array<string,mixed> $payload
     * @return array{status:string,reference:?string,response:array<string,mixed>}
     */
    public function submit(array $payload, string $idempotencyKey): array;
}
