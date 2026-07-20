<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->isAdmin() || $user->id === $appointment->user_id;
    }

    public function updateStatus(User $user, Appointment $appointment): bool
    {
        return $user->isAdmin();
    }
}
