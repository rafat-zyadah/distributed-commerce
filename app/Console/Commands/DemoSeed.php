<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeed extends Command
{
    protected $signature = 'demo:seed';
    protected $description = 'Create a small, realistic dataset for the project demo';

    public function handle(): int
    {
        DB::transaction(function () {
            $now = now();

            DB::table('users')->updateOrInsert(
                ['email' => 'admin@demo.com'],
                [
                    'name' => 'Store Admin',
                    'password' => Hash::make('DemoAdmin123'),
                    'role' => 'admin',
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            DB::table('users')->updateOrInsert(
                ['email' => 'customer@demo.com'],
                [
                    'name' => 'Demo Customer',
                    'password' => Hash::make('DemoCustomer123'),
                    'role' => 'customer',
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $customer = DB::table('users')
                ->where('email', 'customer@demo.com')
                ->first();

            $products = [
                ['Classic T-Shirt', 'Comfortable everyday cotton T-shirt', 'Fashion', 19.99, 40],
                ['Running Sneakers', 'Lightweight shoes for daily running', 'Fashion', 79.50, 25],
                ['Leather Backpack', 'Durable backpack for work and travel', 'Fashion', 54.00, 18],
                ['Wireless Headphones', 'Wireless headphones with clear sound', 'Tech', 89.99, 20],
                ['Smart Watch', 'Smart watch with fitness tracking', 'Tech', 119.00, 15],
                ['Bluetooth Speaker', 'Portable speaker with rich sound', 'Tech', 39.99, 30],
                ['Desk Lamp', 'Modern LED desk lamp', 'Home', 24.50, 22],
                ['Ceramic Coffee Mug', 'Simple ceramic mug for daily use', 'Home', 12.00, 50],
                ['Classic Sunglasses', 'Stylish sunglasses with UV protection', 'Accessories', 29.99, 35],
                ['Travel Suitcase', 'Lightweight suitcase with wheels', 'Accessories', 99.00, 12],
            ];

            $ids = [];

            foreach ($products as [$name, $description, $category, $price, $stock]) {
                $existing = DB::table('products')->where('name', $name)->first();

                $data = [
                    'name' => $name,
                    'description' => $description,
                    'category' => $category,
                    'price' => $price,
                    'stock' => $stock,
                    'status' => 'active',
                    'updated_at' => $now,
                    'created_at' => $now,
                ];

                if ($existing) {
                    DB::table('products')->where('id', $existing->id)->update($data);
                    $ids[$name] = $existing->id;
                } else {
                    $ids[$name] = DB::table('products')->insertGetId($data);
                }
            }

            // Create the two demo orders only once.
            $existingOrders = DB::table('orders')
                ->where('user_id', $customer->id)
                ->count();

            if ($existingOrders === 0) {
                $orders = [
                    [
                        'status' => 'delivered',
                        'items' => [
                            ['Classic T-Shirt', 2],
                            ['Leather Backpack', 1],
                        ],
                    ],
                    [
                        'status' => 'pending',
                        'items' => [
                            ['Wireless Headphones', 1],
                            ['Ceramic Coffee Mug', 2],
                        ],
                    ],
                ];

                foreach ($orders as $orderData) {
                    $total = 0;
                    $lines = [];

                    foreach ($orderData['items'] as [$productName, $quantity]) {
                        $product = DB::table('products')->where('id', $ids[$productName])->first();
                        $total += (float) $product->price * $quantity;

                        $lines[] = [
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'unit_price' => $product->price,
                        ];
                    }

                    $orderId = DB::table('orders')->insertGetId([
                        'user_id' => $customer->id,
                        'status' => $orderData['status'],
                        'total' => round($total, 2),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    foreach ($lines as &$line) {
                        $line['order_id'] = $orderId;
                    }
                    unset($line);

                    DB::table('order_items')->insert($lines);
                }
            }

            $notifications = [
                ['Order placed', 'Your demo order has been placed successfully.'],
                ['Welcome to our store', 'Your customer account is ready to use.'],
            ];

            foreach ($notifications as [$title, $message]) {
                $exists = DB::table('app_notifications')
                    ->where('user_id', $customer->id)
                    ->where('title', $title)
                    ->exists();

                if (! $exists) {
                    DB::table('app_notifications')->insert([
                        'user_id' => $customer->id,
                        'title' => $title,
                        'message' => $message,
                        'event_key' => (string) Str::uuid(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });

        $this->info('Demo data created successfully.');
        $this->info('Admin: admin@demo.com / DemoAdmin123');
        $this->info('Customer: customer@demo.com / DemoCustomer123');

        return self::SUCCESS;
    }
}