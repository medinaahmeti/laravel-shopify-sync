<?php

namespace Tests\Feature;

use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_command_paginates_and_upserts(): void
    {
        $shop = Shop::create([
            'domain' => 's.myshopify.com',
            'access_token' => Crypt::encryptString('tok'),
        ]);

        Http::fake([
            'https://s.myshopify.com/admin/api/*/products.json?limit=250' => Http::response([
                'products' => [
                    [
                        'id' => 11, 'title' => 'A', 'body_html' => 'desc',
                        'variants' => [['id' => 101, 'price' => '9.99', 'inventory_quantity' => 5]],
                        'images' => [['id' => 201, 'src' => 'http://img/1']]
                    ],
                ],
            ], 200, [
                'Link' => '<https://s.myshopify.com/admin/api/2024-10/products.json?page_info=NEXT&limit=250>; rel="next"'
            ]),
            'https://s.myshopify.com/admin/api/*/products.json?page_info=NEXT&limit=250' => Http::response([
                'products' => [
                    [
                        'id' => 12, 'title' => 'B',
                        'variants' => [['id' => 102, 'price' => '19.99', 'inventory_quantity' => 2]],
                        'images' => [['id' => 202, 'src' => 'http://img/2']]
                    ],
                ],
            ], 200, ['Link' => ''])
        ]);

        Artisan::call('shopify:sync', ['shopDomain' => $shop->domain]);

        $this->assertDatabaseHas('products', ['shopify_id' => 11, 'title' => 'A']);
        $this->assertDatabaseHas('products', ['shopify_id' => 12, 'title' => 'B']);
        $this->assertDatabaseHas('product_variants', ['shopify_id' => 101]);
        $this->assertDatabaseHas('product_images', ['shopify_id' => 202]);
    }
}
