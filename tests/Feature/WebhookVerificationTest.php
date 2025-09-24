<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_signature_verification(): void
    {
        config(['services.shopify.secret' => 'secret']);

        $body = json_encode([
            'id' => 9999999999999,
            'title' => 'Webhook Test',
            'variants' => [],
            'images' => [],
        ], JSON_UNESCAPED_SLASHES);

        $hmac = base64_encode(hash_hmac('sha256', $body, 'secret', true));

        $this->call(
            'POST',
            '/webhooks/products/create',
            [], // params
            [], // cookies
            [], // files
            [   // server (headers must be prefixed with HTTP_ where applicable)
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X-Shopify-Hmac-Sha256' => $hmac,
                'HTTP_X-Shopify-Shop-Domain' => 'bericelo.myshopify.com',
            ],
            $body // raw content
        )->assertNoContent();
    }

    public function test_invalid_signature_is_rejected(): void
    {
        config(['services.shopify.secret' => 'secret']);

        $body = json_encode(['id' => 1], JSON_UNESCAPED_SLASHES);

        $this->call(
            'POST',
            '/webhooks/products/create',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X-Shopify-Hmac-Sha256' => 'bad',
                'HTTP_X-Shopify-Shop-Domain' => 'bericelo.myshopify.com',
            ],
            $body
        )->assertStatus(401);
    }
}
