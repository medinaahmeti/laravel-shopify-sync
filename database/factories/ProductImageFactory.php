<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'shopify_id' => $this->faker->unique()->numberBetween(1_000_000, 9_999_999),
            'src' => $this->faker->imageUrl(),
            'position' => 1,
            'alt' => null,
        ];
    }
}
