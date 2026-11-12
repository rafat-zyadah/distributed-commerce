<?php

namespace App\Console\Commands;

use App\Modules\Products\Services\ProductCatalogCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Fills the database with a realistic data set, so slow queries become visible.
 *
 * Rows are written in chunks with plain SQL: no model events, and no Faker
 * (the production image is built with --no-dev). Running it again only adds
 * what is missing, so it is safe to repeat.
 */
class PerfSeed extends Command
{
    protected $signature = 'perf:seed
        {--orders=300000 : Number of orders the table should contain in total}
        {--customers=2000 : Number of customer accounts (perf-customer-N@example.test)}
        {--products=5000 : Number of products the table should contain in total}';

    protected $description = 'Create a large data set (orders, order items, customers, products) for performance tests';

    public const CUSTOMER_PASSWORD = 'Perf12345';
    public const ADMIN_EMAIL = 'admin@test.com';
    public const ADMIN_PASSWORD = 'Admin1234';

    private const ORDER_BATCH = 1000;
    private const ITEM_CHUNK = 2000;
    private const CHUNK = 500;
    private const CATEGORIES = ['books', 'electronics', 'clothes', 'games', 'home'];

    public function handle(ProductCatalogCache $catalog): int
    {
        DB::connection()->disableQueryLog();

        $this->ensureAdmin();
        $customerIds = $this->ensureCustomers((int) $this->option('customers'));
        $this->ensureProducts((int) $this->option('products'));

        if ($customerIds === []) {
            $this->error('No customers: use --customers=N with N >= 1.');

            return self::FAILURE;
        }

        $this->seedOrders($customerIds, (int) $this->option('orders'));

        // Bulk inserts skip the model observers, so invalidate the catalog cache explicitly.
        $catalog->invalidate();

        // Not run in tests: ANALYZE TABLE causes an implicit commit in MySQL, which would end the test transaction.
        if (DB::getDriverName() === 'mysql' && ! $this->laravel->environment('testing')) {
            // Refresh optimizer statistics, so EXPLAIN reflects the new data volume.
            DB::statement('ANALYZE TABLE users, products, orders, order_items');
        }

        $this->info('Done. Next: php artisan perf:explain');

        return self::SUCCESS;
    }

    private function ensureAdmin(): void
    {
        $now = now()->toDateTimeString();

        DB::table('users')->insertOrIgnore([
            'name' => 'Admin',
            'email' => self::ADMIN_EMAIL,
            'password' => Hash::make(self::ADMIN_PASSWORD),
            'role' => 'admin',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Make sure the account used by the smoke test and the load test is an admin.
        DB::table('users')->where('email', self::ADMIN_EMAIL)->update(['role' => 'admin']);
    }

    /**
     * @return array<int, int> ids of the perf customers
     */
    private function ensureCustomers(int $count): array
    {
        if ($count > 0) {
            $now = now()->toDateTimeString();
            $password = Hash::make(self::CUSTOMER_PASSWORD);

            $rows = [];
            for ($i = 1; $i <= $count; $i++) {
                $rows[] = [
                    'name' => "Perf Customer {$i}",
                    'email' => "perf-customer-{$i}@example.test",
                    'password' => $password,
                    'role' => 'customer',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, self::CHUNK) as $chunk) {
                DB::table('users')->insertOrIgnore($chunk);
            }
        }

        if ($count <= 0) {
            return [];
        }

        $emails = [];
        for ($i = 1; $i <= $count; $i++) {
            $emails[] = "perf-customer-{$i}@example.test";
        }

        return DB::table('users')
            ->whereIn('email', $emails)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function ensureProducts(int $target): void
    {
        $missing = $target - (int) DB::table('products')->count();

        if ($missing <= 0) {
            return;
        }

        $now = now()->toDateTimeString();

        $rows = [];
        for ($i = 1; $i <= $missing; $i++) {
            $rows[] = [
                'name' => "Perf product {$i}",
                'description' => 'Generated for performance tests.',
                'category' => self::CATEGORIES[array_rand(self::CATEGORIES)],
                'price' => random_int(500, 50000) / 100,
                'stock' => random_int(0, 500),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            DB::table('products')->insert($chunk);
        }

        $this->line("Added {$missing} products.");
    }

    /**
     * @param  array<int, int>  $customerIds
     */
    private function seedOrders(array $customerIds, int $target): void
    {
        $missing = $target - (int) DB::table('orders')->count();

        if ($missing <= 0) {
            $this->line("Orders already at {$target}, nothing to add.");

            return;
        }

        $prices = DB::table('products')->where('status', 'active')->pluck('price', 'id')->all();
        $productIds = array_keys($prices);

        if ($productIds === []) {
            throw new RuntimeException('There are no active products to order.');
        }

        $this->line("Adding {$missing} orders...");
        $bar = $this->output->createProgressBar($missing);
        $bar->start();

        $done = 0;
        while ($done < $missing) {
            $batch = min(self::ORDER_BATCH, $missing - $done);
            $lastId = (int) DB::table('orders')->max('id');

            $orderRows = [];
            $linesPerOrder = [];

            for ($i = 0; $i < $batch; $i++) {
                $createdAt = now()->subSeconds(random_int(0, 365 * 24 * 3600))->toDateTimeString();
                $lines = [];
                $total = 0.0;

                for ($j = random_int(1, 4); $j > 0; $j--) {
                    $productId = $productIds[array_rand($productIds)];
                    $quantity = random_int(1, 3);
                    $unitPrice = (float) $prices[$productId];

                    $lines[] = [
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                    ];
                    $total += $unitPrice * $quantity;
                }

                $orderRows[] = [
                    'user_id' => $customerIds[array_rand($customerIds)],
                    'status' => $this->randomStatus(),
                    'total' => round($total, 2),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
                $linesPerOrder[] = $lines;
            }

            DB::table('orders')->insert($orderRows);

            // The new orders are the rows above $lastId, in insertion order.
            $orderIds = DB::table('orders')
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->pluck('id')
                ->all();

            if (count($orderIds) !== $batch) {
                throw new RuntimeException("Expected {$batch} new order ids, found ".count($orderIds).'.');
            }

            $itemRows = [];
            foreach ($orderIds as $index => $orderId) {
                foreach ($linesPerOrder[$index] as $line) {
                    $itemRows[] = $line + ['order_id' => $orderId];
                }
            }

            foreach (array_chunk($itemRows, self::ITEM_CHUNK) as $chunk) {
                DB::table('order_items')->insert($chunk);
            }

            $done += $batch;
            $bar->advance($batch);
        }

        $bar->finish();
        $this->newLine();
    }

    /** Roughly: 10% pending, 20% paid, 15% shipped, 50% delivered, 5% cancelled. */
    private function randomStatus(): string
    {
        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 10 => 'pending',
            $roll <= 30 => 'paid',
            $roll <= 45 => 'shipped',
            $roll <= 95 => 'delivered',
            default => 'cancelled',
        };
    }
}
