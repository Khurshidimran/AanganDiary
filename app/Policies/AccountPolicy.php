<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('accounting.manage');
    }

    public function view(User $user, Account $account): bool
    {
        return $user->can('accounting.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('accounting.manage');
    }

    public function update(User $user, Account $account): bool
    {
        return $user->can('accounting.manage');
    }

    public function delete(User $user, Account $account): bool
    {
        return $user->can('accounting.manage') && ! $account->is_system;
    }
}
