<?php

namespace App\Policies;

use App\Models\PurchaseReceipt;
use App\Models\User;

class PurchaseReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchase_receipts.view');
    }

    public function view(User $user, PurchaseReceipt $purchaseReceipt): bool
    {
        return $user->can('purchase_receipts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('purchase_receipts.create');
    }
}
