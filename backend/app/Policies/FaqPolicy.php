<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Faq;
use App\Models\User;

class FaqPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Faq $faq): bool
    {
        return $user->isAdmin() && $user->business_id === $faq->business_id;
    }

    public function delete(User $user, Faq $faq): bool
    {
        return $user->isAdmin() && $user->business_id === $faq->business_id;
    }
}
