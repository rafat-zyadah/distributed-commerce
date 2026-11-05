<?php

namespace Database\Factories;

use App\Modules\Products\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['books', 'electronics', 'clothes']),
            'price' => fake()->randomFloat(2, 5, 500),
            'stock' => fake()->numberBetween(1, 50),
            'status' => Product::STATUS_ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => Product::STATUS_INACTIVE]);
    }
}
