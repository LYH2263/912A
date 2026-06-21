<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU####')),
            'category_id' => Category::factory(),
            'description' => $this->faker->paragraph,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'cost_price' => $this->faker->randomFloat(2, 5, 800),
            'status' => $this->faker->randomElement(['active', 'inactive', 'sold_out']),
            'stock_quantity' => $this->faker->numberBetween(0, 1000),
            'low_stock_threshold' => 10,
            'weight' => $this->faker->randomFloat(2, 0.1, 10),
        ];
    }
}
