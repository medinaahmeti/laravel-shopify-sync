<?php

namespace App\Services;

use App\Models\Shop;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ShopifyClient
{
    public function __construct(
        private readonly string $shopDomain,
        private readonly string $token,
        private readonly string $apiVersion
    ) {}

    public static function forShop(Shop $shop): self
    {
        return new self(
            $shop->domain,
            $shop->getDecryptedToken(),
            config('services.shopify.version')
        );
    }

    public function get(string $path): Response
    {
        return $this->request('get', $path);
    }

    public function post(string $path, array $payload): Response
    {
        return $this->request('post', $path, $payload);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
//    public function request(string $method, string $path, array $payload = []): Response
//    {
//        $base = "https://{$this->shopDomain}/admin/api/{$this->apiVersion}";
//        $url = str_starts_with($path, 'http') ? $path : $base . $path;
//
//        $resp = Http::withToken($this->token)
//            ->acceptJson()
//            ->retry(3, 500, function ($exception) {
//                 $status = optional($exception->response)->status();
//                return in_array($status, [429, 500, 502, 503, 504], true);
//            })
//            ->send(strtoupper($method), $url, ['json' => $payload]);
//
//          if ($resp->status() === 429) {
//            usleep(800_000); // WHY: backoff for rate limit
//            $resp = Http::withToken($this->token)
//                ->acceptJson()
//                ->send(strtoupper($method), $url, ['json' => $payload]);
//        }
//
//        return $resp->throw();
//    }

// FILE: app/Services/ShopifyClient.php (drop-in replacement for request() + helper)
    public function request(string $method, string $path, array $payload = []): \Illuminate\Http\Client\Response
    {
        $base = "https://{$this->shopDomain}/admin/api/{$this->apiVersion}";
        $url  = str_starts_with($path, 'http') ? $path : $base . $path;

        $attempts = 0;

        while (true) {
            $attempts++;

            $resp = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->get($url);
            // Gentle throttle if near the bucket limit
//            $this->maybeThrottle($resp);

            // Handle throttling explicitly
            if ($resp->status() === 429) {
                $retryAfter = (int) ($resp->header('Retry-After') ?? 1);
                usleep(max(1, $retryAfter) * 1_000_000);
                if ($attempts < 5) {
                    continue;
                }
                $this->throwWithBody($resp);
            }

            if ($resp->failed()) {
                $this->throwWithBody($resp);
            }

            return $resp;
        }
    }

    /** Include Shopify's response body in exceptions for easier debugging. */
    private function throwWithBody(\Illuminate\Http\Client\Response $resp): void
    {
        $status = $resp->status();
        $body   = $resp->body();
        $url    = method_exists($resp, 'effectiveUri') ? (string) $resp->effectiveUri() : 'n/a';

        throw new \Illuminate\Http\Client\RequestException(
            $resp,
            "Shopify {$status} on {$url}\n{$body}"
        );
    }

    public function nextLink(Response $resp): ?string
    {
        $link = $resp->header('Link');
        if (!$link) return null;

        foreach (explode(',', $link) as $part) {
            if (str_contains($part, 'rel="next"') && preg_match('/<([^>]+)>/', $part, $m)) {
                $nextUrl = $m[1];
                $parsed = parse_url($nextUrl);
                return ($parsed['path'] ?? '') . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
            }
        }
        return null;
    }
}
