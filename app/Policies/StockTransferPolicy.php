<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;

class StockTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock_transfers.view');
    }

    public function view(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock_transfers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('stock_transfers.create');
    }

    public function update(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock_transfers.edit') && $stockTransfer->isEditable();
    }

    public function delete(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock_transfers.delete') && $stockTransfer->isEditable();
    }

    public function request(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock_transfers.edit') && $stockTransfer->status === StockTransfer::STATUS_DRAFT;
    }

    public function approve(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock_transfers.approve') && $stockTransfer->status === StockTransfer::STATUS_REQUESTED;
    }

    public function dispatch(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock_transfers.edit') && $stockTransfer->status === StockTransfer::STATUS_APPROVED;
    }

    public function receive(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock_transfers.edit') && $stockTransfer->status === StockTransfer::STATUS_IN_TRANSIT;
    }

    public function cancel(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->can('stock_transfers.edit') && $stockTransfer->canBeCancelled();
    }
}
