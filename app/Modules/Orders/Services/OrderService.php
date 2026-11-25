<?php

namespace App\Modules\Orders\Services;

use App\Modules\Notifications\Services\NotificationService;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Products\Models\Product;
use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;
use RuntimeException;

class OrderService
{
    public function __construct(
        private NotificationService $notifications,
        private InventoryGrpc $inventory,
    ) {}

    /**
     * @param array<int, array{product_id: int|string, quantity: int|string}> $items
     */
    public function place(User $customer, array $items): Order
    {
        $products = Product::query()
            ->whereIn('id', collect($items)->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $lines = [];
        $reservations = [];
        $total = 0;

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $quantity = (int) $item['quantity'];
            $product = $products->get($productId);

            if (! $product || $product->status !== Product::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'items' => ["Product {$productId} is not available."],
                ]);
            }

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'items' => ['Quantity must be greater than zero.'],
                ]);
            }

            $lines[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->price,
            ];

            $total += (float) $product->price * $quantity;
        }

        try {
            // لا نبدأ حجزًا حتى نتحقق من جميع المنتجات أولًا.
            foreach ($lines as $line) {
                $this->inventory->reserve(
                    $line['product_id'],
                    (int) $line['quantity']
                );

                $reservations[] = $line;
            }

            $order = DB::transaction(function () use ($customer, $lines, $total) {
                $order = Order::create([
                    'user_id' => $customer->id,
                    'status' => Order::STATUS_PENDING,
                    'total' => round($total, 2),
                ]);

                // One INSERT for all lines. Calling createMany() would send one INSERT per line.
                OrderItem::query()->insert(array_map(
                    fn (array $line) => $line + ['order_id' => $order->id],
                    $lines
                ));

                $this->notifications->send(
                    $customer->id,
                    'Order placed',
                    "Your order #{$order->id} was placed successfully."
                );

                return $order->load('items');
            });
        } catch (Throwable $exception) {
            // إذا فشل حفظ الطلب بعد حجز بعض الكميات، نحاول إعادتها.
            foreach (array_reverse($reservations) as $line) {
                try {
                    $this->inventory->release(
                        $line['product_id'],
                        (int) $line['quantity']
                    );
                } catch (Throwable $compensationException) {
                    report($compensationException);
                }
            }

            // حوّلوا حالات نقص المخزون إلى خطأ تحقق واضح للـ API.
            if ($exception instanceof RuntimeException) {
                throw ValidationException::withMessages([
                    'items' => [
                        'Inventory reservation failed. Check stock or try again.',
                    ],
                ]);
            }

            throw $exception;
        }

        return $order;
    }

    public function changeStatus(Order $order, string $newStatus): Order
    {
        $released = [];

        try {
            $updated = DB::transaction(function () use ($order, $newStatus, &$released) {
                // items are loaded now, so the loop below does not run one query per item.
                $order = Order::query()
                    ->whereKey($order->getKey())
                    ->lockForUpdate()
                    ->with('items')
                    ->firstOrFail();

                if (
                    $order->status === Order::STATUS_CANCELLED
                    && $newStatus !== Order::STATUS_CANCELLED
                ) {
                    throw ValidationException::withMessages([
                        'status' => ['A cancelled order cannot be reopened.'],
                    ]);
                }

                if (
                    $newStatus === Order::STATUS_CANCELLED
                    && $order->status !== Order::STATUS_CANCELLED
                ) {
                    foreach ($order->items as $item) {
                        $this->inventory->release(
                            $item->product_id,
                            (int) $item->quantity
                        );

                        $released[] = [
                            'product_id' => $item->product_id,
                            'quantity' => (int) $item->quantity,
                        ];
                    }
                }

                $order->update(['status' => $newStatus]);

                $this->notifications->send(
                    $order->user_id,
                    'Order status updated',
                    "Your order #{$order->id} is now {$newStatus}."
                );

                return $order->load('items');
            });
        } catch (Throwable $exception) {
            // إذا فشلت معاملة MySQL بعد إرجاع المخزون، نحاول إعادة حجزه.
            foreach (array_reverse($released) as $line) {
                try {
                    $this->inventory->reserve(
                        $line['product_id'],
                        $line['quantity']
                    );
                } catch (Throwable $compensationException) {
                    report($compensationException);
                }
            }

            throw $exception;
        }

        return $updated;
    }
}
