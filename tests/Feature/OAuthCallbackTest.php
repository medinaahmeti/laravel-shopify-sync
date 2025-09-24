<?php

namespace Tests\Feature;

use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OAuthCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_oauth_callback_hmac_ok(): void
    {
        config(['services.shopify.key' => 'k', 'services.shopify.secret' => 's']);

        Http::fake([
            'https://test.myshopify.com/admin/oauth/access_token' => Http::response(['access_token' => 'tok'], 200),
            'https://test.myshopify.com/admin/api/*/webhooks.json' => Http::response(['webhook' => []], 201),
        ]);

        $query = [
            'shop' => 'test.myshopify.com',
            'code' => 'abc',
            'state' => 'xyz',
            'timestamp' => '123',
        ];
        ksort($query);
        $message = urldecode(http_build_query($query));
        $hmac = hash_hmac('sha256', $message, 's');

        $this->withSession(['shopify_oauth_state' => 'xyz'])
            ->get('/oauth/callback?' . http_build_query($query + ['hmac' => $hmac]))
            ->assertRedirect('/installed');

        $this->assertDatabaseHas('shops', ['domain' => 'test.myshopify.com']);
        $this->assertEquals('tok', Crypt::decryptString(Shop::first()->access_token));
    }
}
