<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.view');
    }

    public function create(User $user): bool
    {
        return $user->can('orders.create');
    }

    public function confirm(User $user, Order $order): bool
    {
        return $user->can('orders.edit') && $order->canBeConfirmed();
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->can('orders.edit') && $order->canBeCancelled();
    }

    public function recordPayment(User $user, Order $order): bool
    {
        return $user->can('orders.record-payment') && $order->payment_type === Order::PAYMENT_TYPE_CREDIT;
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->can('orders.delete') && $order->canBeDeleted();
    }
}
