<?php

declare(strict_types=1);

namespace App\Domain\Security\Enums;

enum RoleName: string
{
    case ADMINISTRATOR = 'Administrador';
    case MANAGER = 'Gerente';
    case RENTAL_AGENT = 'Agente de alquiler';
    case INSPECTOR = 'Inspector';
}
