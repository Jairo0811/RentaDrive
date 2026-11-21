<?php

declare(strict_types=1);

// Lo consume Illuminate\Http\Middleware\TrustProxies. Vacío = no se confía en ningún proxy.
// Detrás de un túnel/proxy local usa, por ejemplo, TRUSTED_PROXIES=127.0.0.1 (IP/CIDR separados por coma).
return [
    'proxies' => env('TRUSTED_PROXIES') ?: null,
];
