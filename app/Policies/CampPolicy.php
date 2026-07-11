<?php

namespace App\Policies;

use App\Models\Camp;
use App\Models\User;

class CampPolicy
{
    /**
     * Any member (owner or leader) may view and edit the camp program.
     */
    public function view(User $user, Camp $camp): bool
    {
        return $camp->hasMember($user);
    }

    /**
     * Members may edit the program (activities, days, entries, slots).
     */
    public function update(User $user, Camp $camp): bool
    {
        return $camp->hasMember($user);
    }

    /**
     * Only the owner may delete the camp.
     */
    public function delete(User $user, Camp $camp): bool
    {
        return $camp->owner_id === $user->id;
    }

    /**
     * Only the owner may invite/remove members and change settings.
     */
    public function manageMembers(User $user, Camp $camp): bool
    {
        return $camp->owner_id === $user->id;
    }
}
