<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class BusinessHourPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }
}
