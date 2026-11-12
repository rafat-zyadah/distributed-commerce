<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Performance indexes (Requirement 3)
|--------------------------------------------------------------------------
|
| This file is in database/migrations/performance/ on purpose. A plain
| "php artisan migrate" (which the app container runs on every start) does
| NOT load it, so the "before" measurement can run without these indexes.
|
|   Apply:   php artisan migrate --path=database/migrations/performance
|   Undo:    php artisan migrate:rollback --path=database/migrations/performance --step=1
|
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Customer list: WHERE user_id = ? ORDER BY id DESC (no filesort).
            $table->index(['user_id', 'id'], 'orders_user_id_id_index');

            // Admin list filtered by status: WHERE status = ? ORDER BY id DESC.
            // Without this index MySQL reads every matching row and sorts them.
            $table->index(['status', 'id'], 'orders_status_id_index');
        });

        Schema::table('products', function (Blueprint $table) {
            // Catalog: WHERE status = 'active' [AND category = ?] ORDER BY id.
            $table->index(['status', 'category', 'id'], 'products_status_category_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_id_id_index');
            $table->dropIndex('orders_status_id_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_status_category_id_index');
        });
    }
};
