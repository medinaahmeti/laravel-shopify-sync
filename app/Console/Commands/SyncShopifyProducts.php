<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Services\ShopifyClient;
use App\Services\ShopifyNormalizer;
use Illuminate\Console\Command;

class SyncShopifyProducts extends Command
{
    protected $signature = 'shopify:sync {shopDomain}';
    protected $description = 'Fetch all Shopify products and upsert into local DB';

    public function handle(): int
    {
        $shop = Shop::where('domain', $this->argument('shopDomain'))->firstOrFail();
        $client = ShopifyClient::forShop($shop);
        $next = '/products.json?limit=250';

        while ($next) {
            $resp = $client->get($next);
             $products = $resp->json('products', []);

            foreach ($products as $p) {
                [$productData, $variants, $images] = ShopifyNormalizer::normalizeProduct($p, $shop);

                $product = Product::updateOrCreate(
                    ['shop_id' => $shop->id, 'shopify_id' => $productData['shopify_id']],
                    $productData
                );

                ProductVariant::where('product_id', $product->id)->delete();
                if (!empty($variants)) {
                    foreach ($variants as &$v) { $v['product_id'] = $product->id; }
                    ProductVariant::insert($variants);
                }

                ProductImage::where('product_id', $product->id)->delete();
                if (!empty($images)) {
                    foreach ($images as &$i) { $i['product_id'] = $product->id; }
                    ProductImage::insert($images);
                }
            }

            $next = $client->nextLink($resp);
        }

        $this->info('Sync complete.');
        return self::SUCCESS;
    }
}
