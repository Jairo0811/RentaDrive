<?php

declare(strict_types=1);

namespace App\Domain\Security\Enums;

enum PermissionName: string
{
    case VIEW_DASHBOARD = 'view dashboard';
    case MANAGE_USERS = 'manage users';
    case VIEW_REPORTS = 'view reports';
    case MANAGE_CONFIGURATION = 'manage configuration';
    case VIEW_AUDIT_LOG = 'view audit log';
    case VIEW_VEHICLES = 'view vehicles';
    case MANAGE_VEHICLES = 'manage vehicles';
    case VIEW_CUSTOMERS = 'view customers';
    case MANAGE_CUSTOMERS = 'manage customers';
    case VIEW_RESERVATIONS = 'view reservations';
    case MANAGE_RESERVATIONS = 'manage reservations';
    case VIEW_RENTALS = 'view rentals';
    case MANAGE_RENTALS = 'manage rentals';
    case MANAGE_CONTRACTS = 'manage contracts';
    case MANAGE_DELIVERIES = 'manage deliveries';
    case MANAGE_INSPECTIONS = 'manage inspections';
    case MANAGE_RETURNS = 'manage returns';
    case VIEW_INVOICES = 'view invoices';
    case MANAGE_INVOICES = 'manage invoices';
    case VIEW_PAYMENTS = 'view payments';
    case MANAGE_PAYMENTS = 'manage payments';
}
