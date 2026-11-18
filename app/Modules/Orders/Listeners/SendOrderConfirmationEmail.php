<?php

namespace App\Modules\Orders\Listeners;

use App\Mail\OrderConfirmationMail;
use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Models\Order;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationEmail implements ShouldQueue
{
    public int $tries = 5;

    public array $backoff = [10, 12, 14, 16];

    public function viaConnection(): string
    {
        return 'rabbitmq';
    }

    public function viaQueue(): string
    {
        return 'emails';
    }

    public function handle(OrderPlaced $event): void
    {
        //throw new \RuntimeException('Testing RabbitMQ retry and DLQ');

        $order = Order::with(['user', 'items'])
            ->findOrFail($event->orderId);

        Mail::to($order->user->email)
            ->send(new OrderConfirmationMail($order));
    }
}