<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;

class JournalEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('journal_entries.view');
    }

    public function view(User $user, JournalEntry $journalEntry): bool
    {
        return $user->can('journal_entries.view');
    }

    public function create(User $user): bool
    {
        return $user->can('journal_entries.create');
    }

    public function void(User $user, JournalEntry $journalEntry): bool
    {
        return $user->can('journal_entries.void');
    }
}
