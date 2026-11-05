<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Modules\Orders\Http\Resources\OrderResource;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    /** Customers get their own orders; admins get all orders. */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(Order::STATUSES)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $user = auth('api')->user();

        $orders = Order::query()
            ->with('items')
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest('id')
            ->paginate($data['per_page'] ?? 10);

        return OrderResource::collection($orders)->response();
    }

    /** Customers only (route middleware role:customer). */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $order = $this->orders->place(auth('api')->user(), $data['items']);

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function show(Order $order): JsonResponse
    {
        Gate::authorize('view', $order); // 403 if the order belongs to someone else

        return (new OrderResource($order->load('items')))->response();
    }

    /** Admin only: policy check plus route middleware role:admin. */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('updateStatus', $order);

        $data = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
        ]);

        $order = $this->orders->changeStatus($order, $data['status']);

        return (new OrderResource($order->load('items')))->response();
    }
}
