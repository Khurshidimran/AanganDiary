<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchase_orders.view');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.view');
    }

    public function create(User $user): bool
    {
        return $user->can('purchase_orders.create');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.edit') && $purchaseOrder->isEditable();
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.delete') && $purchaseOrder->isEditable();
    }

    public function submit(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.edit') && $purchaseOrder->status === PurchaseOrder::STATUS_DRAFT;
    }

    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.approve') && $purchaseOrder->status === PurchaseOrder::STATUS_SUBMITTED;
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.edit') && $purchaseOrder->canBeCancelled();
    }
}
