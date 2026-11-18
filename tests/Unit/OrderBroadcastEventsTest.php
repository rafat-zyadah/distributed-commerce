<?php

namespace Tests\Unit;

use App\Modules\Orders\Events\OrderCreatedForAdmin;
use App\Modules\Orders\Events\OrderStatusUpdated;
use App\Modules\Orders\Models\Order;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use PHPUnit\Framework\TestCase;

class OrderBroadcastEventsTest extends TestCase
{
    private function makeOrder(): Order
    {
        $order = new Order();

        $order->setRawAttributes([
            'id' => 15,
            'user_id' => 7,
            'status' => 'pending',
            'total' => '250.00',
            'created_at' => '2026-11-19 10:00:00',
            'updated_at' => '2026-11-19 10:00:00',
        ]);

        return $order;
    }

    public function test_new_order_is_broadcast_immediately_to_admin_channel(): void
    {
        $event = new OrderCreatedForAdmin($this->makeOrder());

        $this->assertInstanceOf(
            ShouldBroadcastNow::class,
            $event
        );

        $this->assertSame(
            'OrderCreatedForAdmin',
            $event->broadcastAs()
        );

        $this->assertSame(
            'private-admin.orders',
            $event->broadcastOn()[0]->name
        );

        $this->assertSame(
            15,
            $event->broadcastWith()['order']['id']
        );
    }

    public function test_order_status_is_broadcast_immediately_to_its_private_channel(): void
    {
        $order = $this->makeOrder();

        $order->status = 'shipped';

        $event = new OrderStatusUpdated($order);

        $this->assertInstanceOf(
            ShouldBroadcastNow::class,
            $event
        );

        $this->assertSame(
            'OrderStatusUpdated',
            $event->broadcastAs()
        );

        $this->assertSame(
            'private-orders.15',
            $event->broadcastOn()[0]->name
        );

        $this->assertSame(
            'shipped',
            $event->broadcastWith()['order']['status']
        );
    }
}
