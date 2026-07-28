<?php

declare(strict_types=1);

namespace App\Domain\Security\Services;

use App\Models\User;
use Illuminate\Foundation\Application;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class FoundationSummaryService
{
    /**
     * @return array{users: int, roles: int, permissions: int, laravel: string}
     */
    public function get(): array
    {
        return [
            'users' => User::query()->count(),
            'roles' => Role::query()->count(),
            'permissions' => Permission::query()->count(),
            'laravel' => Application::VERSION,
        ];
    }
}
