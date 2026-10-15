<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;

final class Eirv3Access
{
    // Matches the role slugs defined in the Phase 1 RoleSeeder.
    public static function role(?User $user): ?string
    {
        if (! $user || ! $user->role_id) {
            return null;
        }

        return Role::query()->whereKey($user->role_id)->value('slug');
    }

    public static function canCreate(?User $user): bool
    {
        return in_array(self::role($user), ['super_admin', 'user', 'qatu'], true);
    }

    public static function canReview(?User $user): bool
    {
        return in_array(self::role($user), ['super_admin', 'qatu'], true);
    }

    public static function canView(?User $user, int $reporterId): bool
    {
        return self::canReview($user) || (self::canCreate($user) && $user?->id === $reporterId);
    }
}
