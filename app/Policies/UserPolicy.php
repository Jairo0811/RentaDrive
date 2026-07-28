<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Security\Enums\PermissionName;
use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::MANAGE_USERS->value);
    }

    public function view(User $user, User $target): bool
    {
        return $user->is($target) || $user->can(PermissionName::MANAGE_USERS->value);
    }

    public function update(User $user, User $target): bool
    {
        return $user->is($target) || $user->can(PermissionName::MANAGE_USERS->value);
    }

    public function delete(User $user, User $target): bool
    {
        return ! $user->is($target)
            && $user->can(PermissionName::MANAGE_USERS->value);
    }
}
