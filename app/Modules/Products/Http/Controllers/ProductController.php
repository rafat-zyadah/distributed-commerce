<?php

namespace App\Modules\Products\Http\Controllers;

use App\Modules\Products\Http\Resources\ProductResource;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Services\ProductCatalogCache;
use App\Support\Replication\ReadRouter;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductCatalogCache $catalog,
        private readonly ReadRouter $reads,
    ) {}

    /** Public: guests and customers see active products; admins see everything. */
    public function index(Request $request): HttpResponse
    {
        $data = $request->validate([
            'category' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $isAdmin = auth('api')->user()?->isAdmin() ?? false;
        $category = $data['category'] ?? null;
        $perPage = $data['per_page'] ?? 10;

        return $this->cachedResponse(
            $request,
            'list',
            [
                'scope' => $isAdmin ? 'admin' : 'public',
                'category' => $category,
                'per_page' => $perPage,
                'page' => max(1, (int) $request->query('page', 1)),
            ],
            fn () => ProductResource::collection(
                Product::on($this->reads->readConnection())
                    ->when(! $isAdmin, fn ($q) => $q->where('status', Product::STATUS_ACTIVE))
                    ->when($category, fn ($q, $value) => $q->where('category', $value))
                    ->orderBy('id')
                    ->paginate($perPage)
            )->response()->getData(true),
        );
    }

    /** Public: inactive products are hidden (404) from non-admins. */
    public function show(Request $request, int $product): HttpResponse
    {
        $isAdmin = auth('api')->user()?->isAdmin() ?? false;

        return $this->cachedResponse(
            $request,
            'show',
            ['scope' => $isAdmin ? 'admin' : 'public', 'id' => $product],
            function () use ($product, $isAdmin) {
                $item = Product::on($this->reads->readConnection())->find($product);

                // The 404 is thrown before anything is stored, so 404s are never cached.
                if ($item === null || ($item->status !== Product::STATUS_ACTIVE && ! $isAdmin)) {
                    abort(404);
                }

                return (new ProductResource($item))->response()->getData(true);
            },
        );
    }

    /** Admin only (route middleware role:admin). The observer invalidates the cache. */
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

    /**
     * Serves a catalog read with Redis caching and ETag revalidation.
     * $load runs only on a cache miss, and returns the JSON payload as an array.
     */
    private function cachedResponse(Request $request, string $name, array $params, Closure $load): HttpResponse
    {
        if (! $this->catalog->enabled()) {
            return response()->json($load());
        }

        $version = $this->catalog->version();
        $etag = $this->catalog->etag($version, $name, $params);

        // The client already holds this version: answer 304 without touching Redis or MySQL.
        if ($this->clientHoldsTag($request, $etag)) {
            return $this->withCacheHeaders(new HttpResponse('', 304), $etag);
        }

        $payload = $this->catalog->remember($version, $name, $params, $load);

        return $this->withCacheHeaders(response()->json($payload), $etag);
    }

    private function clientHoldsTag(Request $request, string $etag): bool
    {
        foreach ($request->getETags() as $tag) {
            // Weak comparison (RFC 9110): "W/" prefixes are ignored.
            if (ltrim($tag, 'W/') === '"'.$etag.'"') {
                return true;
            }
        }

        return false;
    }

    private function withCacheHeaders(HttpResponse $response, string $etag): HttpResponse
    {
        // Weak validator: the same product data may be serialized slightly differently (for example pagination links).
        $response->setEtag($etag, true);

        // Browsers may keep the response, but must ask the server before reusing it.
        $response->headers->set('Cache-Control', 'private, no-cache');
        $response->headers->set('Vary', 'Authorization');

        return $response;
    }
}
