<?php

namespace Tests\Feature;

use App\Modules\Products\Models\Product;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\InteractsWithJwt;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase, InteractsWithJwt;

    public function test_guests_only_see_active_products(): void
    {
        Product::factory()->create(['name' => 'Visible']);
        Product::factory()->inactive()->create(['name' => 'Hidden']);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Visible');
    }

    public function test_admin_sees_inactive_products_too(): void
    {
        Product::factory()->inactive()->create();
        $admin = User::factory()->admin()->create();

        $this->getJson('/api/v1/products', $this->bearer($admin))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_guest_gets_404_for_an_inactive_product(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->getJson("/api/v1/products/{$product->id}")->assertNotFound();
    }

    public function test_category_filter_and_pagination_work(): void
    {
        Product::factory()->count(3)->create(['category' => 'books']);
        Product::factory()->count(2)->create(['category' => 'games']);

        $this->getJson('/api/v1/products?category=books&per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_customer_cannot_create_a_product(): void
    {
        $customer = User::factory()->create();

        $this->postJson('/api/v1/products', [
            'name' => 'Sneaky', 'category' => 'books', 'price' => 10, 'stock' => 1,
        ], $this->bearer($customer))->assertForbidden();

        $this->assertDatabaseMissing('products', ['name' => 'Sneaky']);
    }

    public function test_guest_cannot_create_a_product(): void
    {
        $this->postJson('/api/v1/products', [
            'name' => 'Sneaky', 'category' => 'books', 'price' => 10, 'stock' => 1,
        ])->assertUnauthorized();
    }

    public function test_admin_creates_a_product_with_an_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $response = $this->post('/api/v1/products', [
            'name' => 'Laptop',
            'category' => 'electronics',
            'price' => 999.5,
            'stock' => 5,
            'image' => UploadedFile::fake()->image('laptop.jpg'),
        ], $this->bearer($admin));

        $response->assertCreated()->assertJsonPath('data.name', 'Laptop');
        $this->assertNotNull($response->json('data.image_url'));
        $this->assertCount(1, Storage::disk('public')->files('products'));
    }

    public function test_admin_changes_product_status(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $this->patchJson("/api/v1/products/{$product->id}/status", ['status' => 'inactive'], $this->bearer($admin))
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');
    }
}
