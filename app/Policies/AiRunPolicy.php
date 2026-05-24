<?php

namespace App\Policies;

use App\Models\AiRun;
use App\Models\User;

class AiRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageContent();
    }

    public function view(User $user, AiRun $aiRun): bool
    {
        return $user->canManageContent();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, AiRun $aiRun): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, AiRun $aiRun): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, AiRun $aiRun): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, AiRun $aiRun): bool
    {
        return $user->isAdmin();
    }
}
