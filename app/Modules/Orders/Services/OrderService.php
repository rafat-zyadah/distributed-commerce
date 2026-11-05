<?php

namespace App\Modules\Orders\Services;

use App\Modules\Notifications\Services\NotificationService;
use App\Modules\Orders\Models\Order;
use App\Modules\Products\Models\Product;
use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private NotificationService $notifications) {}

    /**
     * Place an order. Either every line is reserved and the order is created,
     * or nothing changes at all (the transaction rolls back).
     *
     * @param  array<int, array{product_id: int|string, quantity: int|string}>  $items
     */
    public function place(User $customer, array $items): Order
    {
        return DB::transaction(function () use ($customer, $items) {
            // Row locks: two customers buying the last unit at the same moment
            // are serialized here, so the stock can never go negative.
            $products = Product::query()
                ->whereIn('id', collect($items)->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $total = 0;
            $lines = [];

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];

                /** @var Product|null $product */
                $product = $products->get($productId);

                if (! $product || $product->status !== Product::STATUS_ACTIVE) {
                    throw ValidationException::withMessages([
                        'items' => ["Product {$productId} is not available."],
                    ]);
                }

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => ["Not enough stock for '{$product->name}'. Available: {$product->stock}."],
                    ]);
                }

                $product->decrement('stock', $quantity);

                $lines[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                ];

                $total += (float) $product->price * $quantity;
            }

            $order = Order::create([
                'user_id' => $customer->id,
                'status' => Order::STATUS_PENDING,
                'total' => round($total, 2),
            ]);

            $order->items()->createMany($lines);

            $this->notifications->send(
                $customer->id,
                'Order placed',
                "Your order #{$order->id} was placed successfully."
            );

            return $order->load('items');
        });
    }

    /** Admin changes the status. Cancelling gives the reserved units back to stock. */
    public function changeStatus(Order $order, string $newStatus): Order
    {
        return DB::transaction(function () use ($order, $newStatus) {
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->status === Order::STATUS_CANCELLED && $newStatus !== Order::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'status' => ['A cancelled order cannot be reopened.'],
                ]);
            }

            if ($newStatus === Order::STATUS_CANCELLED && $order->status !== Order::STATUS_CANCELLED) {
                foreach ($order->items as $item) {
                    Product::query()->whereKey($item->product_id)->increment('stock', $item->quantity);
                }
            }

            $order->update(['status' => $newStatus]);

            $this->notifications->send(
                $order->user_id,
                'Order status updated',
                "Your order #{$order->id} is now {$newStatus}."
            );

            return $order;
        });
    }
}
