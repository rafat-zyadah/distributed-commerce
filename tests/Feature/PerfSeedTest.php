<?php

namespace Tests\Feature;

use App\Console\Commands\PerfSeed;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Products\Models\Product;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerfSeedTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, int> */
    private const ARGS = ['--orders' => 40, '--customers' => 4, '--products' => 6];

    public function test_it_creates_the_requested_amounts(): void
    {
        $this->artisan('perf:seed', self::ARGS)->assertSuccessful();

        $this->assertSame(40, Order::count());
        $this->assertSame(6, Product::count());
        $this->assertSame(4, User::where('email', 'like', 'perf-customer-%@example.test')->count());
        $this->assertSame(1, User::where('email', PerfSeed::ADMIN_EMAIL)->where('role', 'admin')->count());
    }

    public function test_every_order_has_valid_status_and_one_to_four_real_lines(): void
    {
        $this->artisan('perf:seed', self::ARGS)->assertSuccessful();

        $linesPerOrder = OrderItem::query()
            ->selectRaw('order_id, COUNT(*) AS lines')
            ->groupBy('order_id')
            ->pluck('lines', 'order_id');

        $this->assertCount(40, $linesPerOrder);
        $this->assertTrue($linesPerOrder->every(fn ($lines) => $lines >= 1 && $lines <= 4));
        $this->assertSame(0, OrderItem::whereNotIn('product_id', Product::query()->select('id'))->count());
        $this->assertSame(0, Order::whereNotIn('status', Order::STATUSES)->count());
    }

    public function test_running_it_again_adds_nothing(): void
    {
        $this->artisan('perf:seed', self::ARGS)->assertSuccessful();
        $this->artisan('perf:seed', self::ARGS)->assertSuccessful();

        $this->assertSame(40, Order::count());
        $this->assertSame(6, Product::count());
        $this->assertSame(4, User::where('email', 'like', 'perf-customer-%@example.test')->count());
    }
}
