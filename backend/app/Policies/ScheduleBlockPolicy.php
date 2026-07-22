<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ScheduleBlock;
use App\Models\User;

class ScheduleBlockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ScheduleBlock $scheduleBlock): bool
    {
        return $user->isAdmin();
    }
}
