<?php

namespace Tests\Feature;

use App\Modules\Notifications\Models\Notification;
use App\Modules\Products\Models\Product;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithJwt;
use Tests\TestCase;

/**
 * These tests are the evidence for the requirement:
 * "a user who tries to reach someone else's data, or an operation outside
 *  their permissions, must be rejected".
 */
class OrderSecurityTest extends TestCase
{
    use RefreshDatabase, InteractsWithJwt;

    private function placeOrder(User $customer, Product $product, int $quantity = 1): int
    {
        return $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
        ], $this->bearer($customer))->assertCreated()->json('data.id');
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $orderId = $this->placeOrder($alice, Product::factory()->create(['stock' => 10]));

        $this->getJson("/api/v1/orders/{$orderId}", $this->bearer($bob))->assertForbidden();
        $this->getJson("/api/v1/orders/{$orderId}", $this->bearer($alice))->assertOk();
    }

    public function test_customer_order_list_contains_only_own_orders(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $this->placeOrder($alice, $product);
        $this->placeOrder($bob, $product);

        $this->getJson('/api/v1/orders', $this->bearer($alice))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_id', $alice->id);
    }

    public function test_customer_cannot_change_an_order_status(): void
    {
        $alice = User::factory()->create();
        $orderId = $this->placeOrder($alice, Product::factory()->create(['stock' => 10]));

        $this->patchJson("/api/v1/orders/{$orderId}/status", ['status' => 'paid'], $this->bearer($alice))
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'pending']);
    }

    public function test_admin_can_view_and_change_any_order(): void
    {
        $alice = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $orderId = $this->placeOrder($alice, Product::factory()->create(['stock' => 10]));

        $this->getJson("/api/v1/orders/{$orderId}", $this->bearer($admin))->assertOk();

        $this->patchJson("/api/v1/orders/{$orderId}/status", ['status' => 'shipped'], $this->bearer($admin))
            ->assertOk()
            ->assertJsonPath('data.status', 'shipped');
    }

    public function test_only_customers_can_place_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock' => 10]);

        $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ], $this->bearer($admin))->assertForbidden();
    }

    public function test_order_reduces_stock_and_insufficient_stock_is_rejected(): void
    {
        $alice = User::factory()->create();
        $product = Product::factory()->create(['stock' => 3]);

        $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ], $this->bearer($alice))->assertUnprocessable();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
        $this->assertDatabaseCount('orders', 0);

        $this->placeOrder($alice, $product, 2);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 1]);
    }

    public function test_cancelling_an_order_returns_the_stock(): void
    {
        $alice = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock' => 3]);
        $orderId = $this->placeOrder($alice, $product, 2);

        $this->patchJson("/api/v1/orders/{$orderId}/status", ['status' => 'cancelled'], $this->bearer($admin))
            ->assertOk();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
    }

    public function test_notifications_are_private_to_their_owner(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $this->placeOrder($alice, Product::factory()->create(['stock' => 10]));

        $this->assertSame(1, Notification::where('user_id', $alice->id)->count());

        $this->getJson('/api/v1/notifications', $this->bearer($bob))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $notificationId = Notification::where('user_id', $alice->id)->value('id');

        $this->patchJson("/api/v1/notifications/{$notificationId}/read", [], $this->bearer($bob))
            ->assertForbidden();

        $this->patchJson("/api/v1/notifications/{$notificationId}/read", [], $this->bearer($alice))
            ->assertOk()
            ->assertJsonPath('data.read_at', fn ($value) => $value !== null);
    }
}
