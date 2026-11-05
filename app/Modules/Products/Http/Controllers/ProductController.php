<?php

namespace App\Modules\Products\Http\Controllers;

use App\Modules\Products\Http\Resources\ProductResource;
use App\Modules\Products\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /** Public: guests and customers see active products; admins see everything. */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $isAdmin = auth('api')->user()?->isAdmin() ?? false;

        $products = Product::query()
            ->when(! $isAdmin, fn ($q) => $q->where('status', Product::STATUS_ACTIVE))
            ->when($data['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->orderBy('id')
            ->paginate($data['per_page'] ?? 10);

        return ProductResource::collection($products)->response();
    }

    /** Public: inactive products are hidden (404) from non-admins. */
    public function show(Product $product): JsonResponse
    {
        $isAdmin = auth('api')->user()?->isAdmin() ?? false;

        if ($product->status !== Product::STATUS_ACTIVE && ! $isAdmin) {
            abort(404);
        }

        return (new ProductResource($product))->response();
    }

    /** Admin only (route middleware role:admin). */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(Product::STATUSES)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($data['image']);

        $data['status'] ??= Product::STATUS_ACTIVE;

        $product = Product::create($data);

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    /** Admin only. */
    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'category' => ['sometimes', 'string', 'max:50'],
            'price' => ['sometimes', 'numeric', 'min:0', 'max:999999.99'],
            'stock' => ['sometimes', 'integer', 'min:0'],
        ]);

        $product->update($data);

        return (new ProductResource($product->fresh()))->response();
    }

    /** Admin only: switch a product between active and inactive. */
    public function changeStatus(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Product::STATUSES)],
        ]);

        $product->update($data);

        return (new ProductResource($product->fresh()))->response();
    }
}
