<?php

namespace App\Console\Commands;

use App\Modules\Orders\Models\Order;
use App\Modules\Products\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Prints the MySQL query plan of the main read queries.
 * Run it before and after: php artisan migrate --path=database/migrations/performance
 *
 * The SQL comes from the same Eloquent builders the app uses, so the plan matches
 * what the endpoints really run (the paginator's COUNT query is not shown).
 */
class PerfExplain extends Command
{
    protected $signature = 'perf:explain {--analyze : Use EXPLAIN ANALYZE, which runs each query and shows real timings}';

    protected $description = 'Show MySQL EXPLAIN plans for the main read queries';

    public function handle(): int
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->error('perf:explain needs a MySQL connection.');

            return self::FAILURE;
        }

        $customerId = DB::table('orders')->value('user_id');

        if ($customerId === null) {
            $this->error('There are no orders yet. Run: php artisan perf:seed');

            return self::FAILURE;
        }

        $queries = [
            'Customer order list (WHERE user_id = ? ORDER BY id DESC LIMIT 10)' => Order::query()
                ->where('user_id', $customerId)
                ->latest('id')
                ->limit(10),

            'Admin order list (WHERE status = paid ORDER BY id DESC LIMIT 10)' => Order::query()
                ->where('status', Order::STATUS_PAID)
                ->latest('id')
                ->limit(10),

            'Product catalog (WHERE status = active AND category = books ORDER BY id LIMIT 10)' => Product::query()
                ->where('status', Product::STATUS_ACTIVE)
                ->where('category', 'books')
                ->orderBy('id')
                ->limit(10),
        ];

        foreach ($queries as $title => $builder) {
            $this->newLine();
            $this->info($title);

            $sql = $builder->toSql();
            $bindings = $builder->getBindings();

            if ($this->option('analyze')) {
                foreach (DB::select('EXPLAIN ANALYZE '.$sql, $bindings) as $row) {
                    $this->line((string) array_values((array) $row)[0]);
                }

                continue;
            }

            $rows = [];
            foreach (DB::select('EXPLAIN '.$sql, $bindings) as $row) {
                $rows[] = [
                    $row->table ?? '',
                    $row->type ?? '',
                    $row->key ?? 'NULL',
                    $row->rows ?? '',
                    $row->Extra ?? '',
                ];
            }

            $this->table(['table', 'type', 'key', 'rows (estimate)', 'Extra'], $rows);
        }

        return self::SUCCESS;
    }
}
