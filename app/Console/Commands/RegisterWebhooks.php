<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Services\ShopifyClient;
use Illuminate\Console\Command;

class RegisterWebhooks extends Command
{
    protected $signature = 'shopify:webhooks:register {shopDomain}';
    protected $description = 'Idempotently register product webhooks (create|update|delete) and print API responses';

    public function handle(): int
    {
        $shop = Shop::where('domain', $this->argument('shopDomain'))->firstOrFail();
        $client = ShopifyClient::forShop($shop);
        $addressBase = rtrim(config('app.url'), '/');

        // 1) Fetch existing
        $existing = $client->get('/webhooks.json')->json('webhooks', []);
        $byTopic = [];
        foreach ($existing as $w) {
            $byTopic[$w['topic']] = $w;
        }

        // 2) Ensure 3 topics
        $topics = ['products/create', 'products/update', 'products/delete'];
        foreach ($topics as $topic) {
            $address = "{$addressBase}/webhooks/{$topic}";
            $needsCreate = true;

            if (isset($byTopic[$topic])) {
                $current = $byTopic[$topic];
                // If address differs, delete & recreate
                if (($current['address'] ?? '') !== $address) {
                    $this->warn("Deleting old webhook for {$topic} @ {$current['address']}");
                    $client->request('DELETE', "/webhooks/{$current['id']}.json");
                } else {
                    $this->info("Already exists: {$topic} → {$address} (id: {$current['id']})");
                    $needsCreate = false;
                }
            }

            if ($needsCreate) {
                $res = $client->post('/webhooks.json', [
                    'webhook' => [
                        'topic'   => $topic,
                        'format'  => 'json',
                        'address' => $address,
                    ],
                ]);
                $this->line("POST /webhooks.json → {$res->status()}");
                $this->line($res->body());

                if ($res->failed()) {
                    $this->error("Failed to create {$topic}");
                    return self::FAILURE;
                }
            }
        }

        // 3) Final list
        $final = $client->get('/webhooks.json')->json('webhooks', []);
        $rows = array_map(fn($w) => [
            'id'        => $w['id'] ?? null,
            'topic'     => $w['topic'] ?? '',
            'address'   => $w['address'] ?? '',
            'created_at'=> $w['created_at'] ?? '',
        ], $final);

        $this->table(['id','topic','address','created_at'], $rows);
        return self::SUCCESS;
    }
}
