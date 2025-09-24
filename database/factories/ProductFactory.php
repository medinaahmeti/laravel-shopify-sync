<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'shopify_id' => $this->faker->unique()->numberBetween(1_000_000, 9_999_999),
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'status' => 'active',
        ];
    }
}
