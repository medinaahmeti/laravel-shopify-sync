<?php

namespace App\Services;

use App\Models\Shop;

class ShopifyNormalizer
{
    public static function normalizeProduct(array $p, Shop $shop): array
    {
        $productData = [
            'shop_id' => $shop->id,
            'shopify_id' => $p['id'],
            'title' => $p['title'] ?? '',
            'description' => $p['body_html'] ?? null,
            'status' => $p['status'] ?? null,
        ];

        $variants = array_map(static function ($v) {
            return [
                'shopify_id' => $v['id'],
                'sku' => $v['sku'] ?? null,
                'price' => isset($v['price']) ? (string) $v['price'] : null,
                'inventory_quantity' => $v['inventory_quantity'] ?? null,
                'option1' => $v['option1'] ?? null,
                'option2' => $v['option2'] ?? null,
                'option3' => $v['option3'] ?? null,
            ];
        }, $p['variants'] ?? []);

        $images = array_map(static function ($img) {
            return [
                'shopify_id' => $img['id'],
                'src' => $img['src'] ?? '',
                'position' => $img['position'] ?? null,
                'alt' => $img['alt'] ?? null,
            ];
        }, $p['images'] ?? []);

        return [$productData, $variants, $images];
    }
}
