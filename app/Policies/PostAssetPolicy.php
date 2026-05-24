<?php

namespace App\Policies;

use App\Models\PostAsset;
use App\Models\User;

class PostAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageContent();
    }

    public function view(User $user, PostAsset $postAsset): bool
    {
        return $user->canManageContent();
    }

    public function create(User $user): bool
    {
        return $user->canManageContent();
    }

    public function update(User $user, PostAsset $postAsset): bool
    {
        return $user->canManageContent();
    }

    public function delete(User $user, PostAsset $postAsset): bool
    {
        return $user->canManageContent();
    }

    public function restore(User $user, PostAsset $postAsset): bool
    {
        return $user->canManageContent();
    }

    public function forceDelete(User $user, PostAsset $postAsset): bool
    {
        return $user->isAdmin();
    }
}
