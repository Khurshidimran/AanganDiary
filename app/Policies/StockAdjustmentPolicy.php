<?php

namespace App\Policies;

use App\Models\StockAdjustment;
use App\Models\User;

class StockAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock_adjustments.view');
    }

    public function view(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $user->can('stock_adjustments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('stock_adjustments.create');
    }

    public function update(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $user->can('stock_adjustments.edit') && $stockAdjustment->isEditable();
    }

    public function delete(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $user->can('stock_adjustments.delete') && $stockAdjustment->isEditable();
    }

    public function post(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $user->can('stock_adjustments.edit') && $stockAdjustment->isEditable();
    }
}
