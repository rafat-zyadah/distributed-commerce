<?php

use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Admin Orders Channel
|--------------------------------------------------------------------------
|
| Only administrators can receive newly created orders.
|
*/

Broadcast::channel('admin.orders', function (User $user) {
    return $user->isAdmin();
});

/*
|--------------------------------------------------------------------------
| Individual Order Channel
|--------------------------------------------------------------------------
|
| Only the customer who owns the order can subscribe.
|
*/

Broadcast::channel('orders.{orderId}', function (
    User $user,
    int $orderId
) {
    $order = Order::find($orderId);

    return $order !== null
        && (int) $order->user_id === (int) $user->id;
});

/*
|--------------------------------------------------------------------------
| Existing User Private Channel
|--------------------------------------------------------------------------
*/

Broadcast::channel('App.Models.User.{id}', function (
    User $user,
    int $id
) {
    return (int) $user->id === (int) $id;
});
