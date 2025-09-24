<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Services\ShopifyClient;
use Illuminate\Console\Command;

class ListWebhooks extends Command
{
    protected $signature = 'shopify:webhooks:list {shopDomain}';
    protected $description = 'List registered webhooks with raw API status/body when empty';

    public function handle(): int
    {
        $shop = Shop::where('domain', $this->argument('shopDomain'))->firstOrFail();
        $client = ShopifyClient::forShop($shop);

        $res = $client->get('/webhooks.json');
        $this->line("GET /webhooks.json → {$res->status()}");
        $hooks = $res->json('webhooks', []);

        if (empty($hooks)) {
            $this->warn('No webhooks returned.');
            $this->line($res->body()); // print raw body for diagnostics
            return $res->successful() ? self::SUCCESS : self::FAILURE;
        }

        $rows = array_map(fn($w) => [
            'id' => $w['id'] ?? null,
            'topic' => $w['topic'] ?? '',
            'address' => $w['address'] ?? '',
            'created_at' => $w['created_at'] ?? '',
        ], $hooks);

        $this->table(['id','topic','address','created_at'], $rows);
        return self::SUCCESS;
    }
}
