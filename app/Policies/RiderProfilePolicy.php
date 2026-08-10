<?php

namespace App\Policies;

use App\Models\RiderProfile;
use App\Models\User;

class RiderProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('riders.view');
    }

    public function view(User $user, RiderProfile $rider): bool
    {
        return $user->can('riders.view');
    }

    public function create(User $user): bool
    {
        return $user->can('riders.create');
    }

    public function update(User $user, RiderProfile $rider): bool
    {
        return $user->can('riders.edit');
    }

    public function delete(User $user, RiderProfile $rider): bool
    {
        return $user->can('riders.delete');
    }
}
