
<x-mail::message>
# Order Confirmation

Hello {{ $order->user->name ?? 'Customer' }},

Your order has been successfully created!

**Order Number:** #{{ $order->id }}

**Order Date:** {{ $order->created_at->format('Y-m-d H:i') }}

**Order Status:** {{ ucfirst($order->status) }}

<x-mail::table>
| Product | Quantity | Unit Price |
|:--------|---------:|-----------:|
@foreach ($order->items as $item)
| {{ $item->product->name ?? 'Product' }} | {{ $item->quantity }} | {{ number_format((float) $item->unit_price, 2) }} |
@endforeach
</x-mail::table>

**Total: {{ number_format((float) $order->total, 2) }}**

Thank you for shopping with us!

We will notify you when your order status changes.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
