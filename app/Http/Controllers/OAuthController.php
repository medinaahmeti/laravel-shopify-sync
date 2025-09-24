<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Services\ShopifyClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    public function install(Request $request)
    {
        $shop = Str::of($request->string('shop'))->lower()->trim()->value();
        abort_unless($shop && str_ends_with($shop, '.myshopify.com'), 422);

        $state = Str::random(32);
        $request->session()->put('shopify_oauth_state', $state);

        $params = http_build_query([
            'client_id' => config('services.shopify.key'),
            'scope' => config('services.shopify.scopes'),
            'redirect_uri' => route('shopify.callback', absolute: false),
            'state' => $state,
        ]);
         return redirect("https://{$shop}/admin/oauth/authorize?{$params}");
    }

    public function callback(Request $request)
    {
        $this->verifyHmac($request->query());
        abort_unless($request->session()->pull('shopify_oauth_state') === $request->string('state')->value(), 419);

        $shop = $request->string('shop')->value();
        $code = $request->string('code')->value();

        $tokenResp = Http::asJson()->post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => config('services.shopify.key'),
            'client_secret' => config('services.shopify.secret'),
            'code' => $code,
        ])->throw()->json();

        $accessToken = $tokenResp['access_token'];

        $shopModel = Shop::updateOrCreate(
            ['domain' => $shop],
            ['access_token' => Crypt::encryptString($accessToken)]
        );

        // Register product webhooks
        $client = ShopifyClient::forShop($shopModel);
        foreach (['products/create', 'products/update', 'products/delete'] as $topic) {
            $client->post('/webhooks.json', [
                'webhook' => [
                    'topic' => $topic,
                    'format' => 'json',
                    'address' => url("/webhooks/{$topic}"),
                ],
            ]);
        }

        return redirect('/installed');
    }

    private function verifyHmac(array $query): void
    {
        $hmac = $query['hmac'] ?? '';
        unset($query['hmac'], $query['signature']);
        ksort($query);
        $message = urldecode(http_build_query($query));
        $computed = hash_hmac('sha256', $message, config('services.shopify.secret'));
        abort_unless(hash_equals($computed, $hmac), 401);
    }
}
