<?php

declare(strict_types=1);

// RentaDrive no ofrece API pública para navegadores de terceros: los únicos endpoints bajo api/* son
// webhooks servidor a servidor. No se permite ningún origen ni credenciales de forma predeterminada.
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['POST'],
    'allowed_origins' => [],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'X-RentaDrive-Signature'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
