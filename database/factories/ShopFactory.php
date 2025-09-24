<?php

namespace Database\Factories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        return [
            'domain' => $this->faker->domainWord().'.myshopify.com',
            'access_token' => Crypt::encryptString('shpat_test'),
        ];
    }
}
