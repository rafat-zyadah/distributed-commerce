<?php

namespace App\Modules\Orders\Policies;

use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;

class OrderPolicy
{
    /** A customer sees only their own orders; an admin sees all. */
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $order->user_id === $user->id;
    }

    /** Only admins change an order's status. */
    public function updateStatus(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}
