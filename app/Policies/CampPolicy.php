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
     * Members may move the program around — unless the owner froze the schedule.
     * Day notes and reviews go through `update` and stay editable while frozen.
     */
    public function editSchedule(User $user, Camp $camp): bool
    {
        return $camp->hasMember($user) && ! $camp->isScheduleLocked();
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
