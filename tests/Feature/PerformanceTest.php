<?php

namespace Tests\Feature;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\InventoryGrpc;
use App\Modules\Products\Models\Product;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Tests\Support\InteractsWithJwt;
use Tests\TestCase;

/**
 * Requirement 3: the catalog cache, ETag / 304 revalidation, invalidation after an edit,
 * and guards against N+1 queries. The replica routing rule is tested in Unit/ReadRouterTest.
 */
class PerformanceTest extends TestCase
{
    use RefreshDatabase, InteractsWithJwt;

    public function test_product_list_and_product_page_are_revalidated_with_an_etag(): void
    {
        $product = Product::factory()->create();
        Product::factory()->create();

        $list = $this->getFresh('/api/v1/products')->assertOk();
        $listEtag = (string) $list->headers->get('ETag');

        $this->assertStringStartsWith('W/"', $listEtag);
        $list->assertHeader('Cache-Control', 'private, no-cache');
        $this->assertStringContainsString('Authorization', (string) $list->headers->get('Vary'));
        $this->assertRevalidated('/api/v1/products', $listEtag);

        $page = $this->getFresh("/api/v1/products/{$product->id}")->assertOk();
        $this->assertRevalidated("/api/v1/products/{$product->id}", (string) $page->headers->get('ETag'));
    }

    public function test_a_cached_product_list_runs_no_sql(): void
    {
        Product::factory()->count(2)->create();
        $this->getFresh('/api/v1/products')->assertOk(); // fills the cache

        $queries = $this->queriesDuring(fn () => $this->getFresh('/api/v1/products')->assertOk());

        $this->assertCount(0, $queries);
    }

    public function test_a_product_edit_is_visible_on_the_very_next_read(): void
    {
        $product = Product::factory()->create(['name' => 'Old name']);
        $admin = User::factory()->admin()->create();

        $oldEtag = (string) $this->getFresh("/api/v1/products/{$product->id}")->assertOk()->headers->get('ETag');
        $this->getFresh('/api/v1/products')->assertJsonPath('data.0.name', 'Old name');

        $this->putJson("/api/v1/products/{$product->id}", ['name' => 'New name'], $this->bearer($admin))
            ->assertOk();

        // The old ETag no longer matches, so the server sends the new data (200, not 304).
        $this->getFresh("/api/v1/products/{$product->id}", ['If-None-Match' => $oldEtag])
            ->assertOk()
            ->assertJsonPath('data.name', 'New name');

        $this->getFresh('/api/v1/products')->assertJsonPath('data.0.name', 'New name');
    }

    public function test_hiding_a_product_takes_effect_at_once(): void
    {
        $product = Product::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->getFresh("/api/v1/products/{$product->id}")->assertOk(); // cached

        $this->patchJson("/api/v1/products/{$product->id}/status", ['status' => 'inactive'], $this->bearer($admin))
            ->assertOk();

        $this->getFresh("/api/v1/products/{$product->id}")->assertNotFound();
        $this->getFresh('/api/v1/products')->assertJsonCount(0, 'data');
    }

    public function test_a_not_found_is_never_cached(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->getFresh("/api/v1/products/{$product->id}")->assertNotFound();

        // A change that skips the model, and so the observer, must still be visible at once.
        DB::table('products')->where('id', $product->id)->update(['status' => 'active']);

        $this->getFresh("/api/v1/products/{$product->id}")->assertOk();
    }

    public function test_guests_and_admins_get_separate_cached_lists(): void
    {
        Product::factory()->create();
        Product::factory()->inactive()->create();
        $admin = User::factory()->admin()->create();

        $this->getFresh('/api/v1/products')->assertJsonCount(1, 'data');
        $this->getFresh('/api/v1/products', $this->bearer($admin))->assertJsonCount(2, 'data');
        $this->getFresh('/api/v1/products')->assertJsonCount(1, 'data');
    }

    public function test_with_the_cache_switched_off_responses_are_not_revalidated(): void
    {
        config(['performance.cache.enabled' => false]);
        Product::factory()->create();

        $response = $this->getFresh('/api/v1/products')->assertOk();

        $this->assertNull($response->headers->get('ETag'));
        $response->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_the_order_list_runs_the_same_queries_however_many_orders_there_are(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['price' => 10]);
        $headers = $this->bearer($customer);

        $this->makeOrders($customer, $product, 2);
        $few = count($this->queriesDuring(fn () => $this->getJson('/api/v1/orders', $headers)->assertOk()));

        $this->makeOrders($customer, $product, 10);
        $many = count($this->queriesDuring(fn () => $this->getJson('/api/v1/orders', $headers)->assertOk()));

        $this->assertSame($few, $many, 'The order list must not run one query per order.');
    }

    public function test_an_order_with_three_lines_is_written_with_one_insert(): void
    {
        $this->mockInventory();
        $customer = User::factory()->create();
        $headers = $this->bearer($customer);
        $items = Product::factory()->count(3)->create(['stock' => 10])
            ->map(fn (Product $product) => ['product_id' => $product->id, 'quantity' => 1])
            ->all();

        $queries = $this->queriesDuring(
            fn () => $this->postJson('/api/v1/orders', ['items' => $items], $headers)->assertCreated()
        );

        $orderItemInserts = collect($queries)->filter(
            fn (array $query) => str_starts_with(strtolower($query['query']), 'insert into `order_items`')
        );

        $this->assertCount(1, $orderItemInserts);
    }

    private function assertRevalidated(string $uri, string $etag): void
    {
        $response = $this->getFresh($uri, ['If-None-Match' => $etag])->assertStatus(304);

        $this->assertSame('', $response->getContent());
    }

    /**
     * The app is reused between requests within one test, and the JWT guard keeps the last
     * user it saw. Forget it first, so a request without a token is really a guest.
     */
    private function getFresh(string $uri, array $headers = []): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->getJson($uri, $headers);
    }

    /**
     * Runs $callback and returns the SQL statements it sent to the database.
     *
     * @return array<int, array{query: string, bindings: array, time: float}>
     */
    private function queriesDuring(callable $callback): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $callback();

            return DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }
    }

    private function makeOrders(User $customer, Product $product, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $order = Order::create([
                'user_id' => $customer->id,
                'status' => Order::STATUS_PAID,
                'total' => $product->price,
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => $product->price,
            ]);
        }
    }

    /** Stock is held by the inventory service, which is not running in unit tests. */
    private function mockInventory(): void
    {
        $this->mock(InventoryGrpc::class, function (MockInterface $mock) {
            $mock->shouldReceive('reserve')->andReturnNull();
            $mock->shouldReceive('release')->andReturnNull();
        });
    }
}
