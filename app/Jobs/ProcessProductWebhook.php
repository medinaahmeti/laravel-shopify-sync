<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Services\ShopifyNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ProcessProductWebhook implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public string $shopDomain,
        public string $event,
        public string $payloadJson
    ) {}

    public function handle(): void
    {
        Log::info("[webhook] {$this->event} from {$this->shopDomain}");

        $shop = Shop::where('domain', $this->shopDomain)->first();
        if (!$shop) return;

        $data = json_decode($this->payloadJson, true);

        if ($this->event === 'delete') {
            Product::where('shop_id', $shop->id)->where('shopify_id', $data['id'])->delete();
            return;
        }

        [$productData, $variants, $images] = ShopifyNormalizer::normalizeProduct($data, $shop);

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
}
