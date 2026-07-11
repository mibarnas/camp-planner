<?php

namespace App\Policies;

use App\Models\ActivityLibrary;
use App\Models\User;

class ActivityLibraryPolicy
{
    /**
     * Any member may browse and use the library's activities.
     */
    public function view(User $user, ActivityLibrary $library): bool
    {
        return $library->hasMember($user);
    }

    /**
     * Members may add and edit activities.
     */
    public function update(User $user, ActivityLibrary $library): bool
    {
        return $library->hasMember($user);
    }

    /**
     * Only the owner may rename/delete the library or manage members directly.
     */
    public function manage(User $user, ActivityLibrary $library): bool
    {
        return $library->owner_id === $user->id;
    }
}
