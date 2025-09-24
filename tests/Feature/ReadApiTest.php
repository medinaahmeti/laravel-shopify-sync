<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.api_token' => 'apitok']);
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/products')->assertStatus(401);
        $this->getJson('/api/products/1')->assertStatus(401);
    }

    public function test_list_with_search_and_pagination(): void
    {
        Product::factory()->create(['title' => 'Blue Shirt']);
        Product::factory()->create(['title' => 'Red Socks']);

        $res = $this->withHeader('Authorization', 'Bearer apitok')
            ->getJson('/api/products?query=Shirt&per_page=1');

        $res->assertOk()
            ->assertJsonPath('data.0.title', 'Blue Shirt')
            ->assertJsonStructure(['data', 'links']);
    }

    public function test_show_includes_variants_and_images(): void
    {
        $p = Product::factory()->create(['title' => 'Gift Card']);
        ProductVariant::factory()->create(['product_id' => $p->id, 'price' => '10.00']);
        ProductImage::factory()->create(['product_id' => $p->id, 'src' => 'http://img/1.jpg']);

        $res = $this->withHeader('Authorization', 'Bearer apitok')
            ->getJson('/api/products/' . $p->id);

        $res->assertOk()
            ->assertJsonPath('title', 'Gift Card')
            ->assertJsonCount(1, 'variants')
            ->assertJsonCount(1, 'images');
    }
}
