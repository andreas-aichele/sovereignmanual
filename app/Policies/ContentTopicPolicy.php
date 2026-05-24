<?php

namespace App\Policies;

use App\Models\ContentTopic;
use App\Models\User;

class ContentTopicPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ContentTopic $contentTopic): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ContentTopic $contentTopic): bool
    {
        return true;
    }

    public function delete(User $user, ContentTopic $contentTopic): bool
    {
        return true;
    }

    public function restore(User $user, ContentTopic $contentTopic): bool
    {
        return true;
    }

    public function forceDelete(User $user, ContentTopic $contentTopic): bool
    {
        return true;
    }
}
