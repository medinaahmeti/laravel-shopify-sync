<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'shopify_id' => $this->faker->unique()->numberBetween(1_000_000, 9_999_999),
            'sku' => $this->faker->ean8(),
            'price' => $this->faker->randomFloat(2, 1, 100),
            'inventory_quantity' => $this->faker->numberBetween(0, 50),
            'option1' => null, 'option2' => null, 'option3' => null,
        ];
    }
}
