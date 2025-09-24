## README.md


````md
# Shopify Sync (Laravel 11)

Minimal Laravel service that installs as a Shopify custom app and keeps local products in sync.

- **1) Shopify OAuth** — install flow, HMAC verification, token persisted per shop.
- **2) Products Sync** — artisan command: fetch all products, upsert normalized schema (title, description, variants, prices, inventory, images) with pagination + rate-limit handling.
- **3) Webhooks** — register + process `products/create|update|delete`, verify signatures, queue worker executes processing.
- **4) Read API** — `GET /api/products` (pagination + text search), `GET /api/products/{id}` (variants & images), Bearer token auth.

---

## 0) Prerequisites

- PHP 8.2+ (8.3/8.4 OK), Composer
- SQLite
- Public HTTPS tunnel (Cloudflare Tunnel)
- Shopify dev store - [testing on: https://bericelo.myshopify.com/]

```bash
composer install
cp .env.example .env
php artisan key:generate
````

---

## 1) .env — required keys

```env
APP_NAME="Shopify Sync"
APP_URL=https://<your-https-tunnel>   # e.g. https://abc.trycloudflare.com

# Shopify Admin API (from your app's API credentials)
SHOPIFY_API_KEY=<API key>             # e.g. d2c96add03...
SHOPIFY_API_SECRET=<API secret key>   # e.g. shpss_xxx... or hex for store-created apps
SHOPIFY_API_VERSION=2025-07
SHOPIFY_SCOPES=read_products,read_inventory,write_webhooks

# Local API token protection
API_TOKEN=dev-api-token

# Queue
QUEUE_CONNECTION=database

# DB…
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shopify_sync
DB_USERNAME=root
DB_PASSWORD=
```

Force HTTPS URLs (required by Shopify):

```php
// app/Providers/AppServiceProvider.php (boot)
use Illuminate\Support\Facades\URL;
public function boot(): void {
  if (str_starts_with((string) config('app.url'), 'https://')) {
    URL::forceRootUrl(config('app.url'));
    URL::forceScheme('https');
  }
}
```

---

## 2) Database & queue

```bash
php artisan migrate
php artisan queue:table && php artisan migrate
```

---

## 3) Start a public HTTPS tunnel

```bash
# Cloudflare Tunnel
brew install cloudflared
cloudflared tunnel --url http://localhost:8000
```

Set that HTTPS in `.env → APP_URL`, then:

```bash
php artisan config:clear && php artisan cache:clear && php artisan route:clear
php artisan serve --host=0.0.0.0 --port=8000
```

---

## 4) Shopify app creation

### A) Store-created custom app (fastest, no OAuth screens)

* Admin → **Apps → Develop apps → Create an app**.
* Copy **API key**, **API secret key**. (Admin token `shpat_…` will be revealed once.)
* Put API key/secret in `.env`.
* **Insert the `shpat_…` token into DB** (encrypted):

```bash
php artisan tinker
```

```php
[yourstore = bericelo]
use App\Models\Shop; use Illuminate\Support\Facades\Crypt;
Shop::updateOrCreate(
  ['domain' => 'yourstore.myshopify.com'],
  ['access_token' => Crypt::encryptString('shpat_xxxxxxxxx')]
);
```

You can now run sync and register webhooks.

---

## 5) Webhooks

Register the 3 product hooks for your current `APP_URL`:

```bash
php artisan shopify:webhooks:register yourstore.myshopify.com
php artisan shopify:webhooks:list yourstore.myshopify.com
# expect:
# products/create|update|delete → https://<your-https>/webhooks/products/<topic>
```

Run the worker:

```bash
php artisan queue:work
```

> **Changing tunnels?** Update `.env APP_URL`, clear config/cache, and re-run `shopify:webhooks:register` to point hooks to the new host.

---

## 6) Products Sync (REST, pagination + rate-limiting)

Sync one shop:

```bash
php artisan shopify:sync yourstore.myshopify.com
```

Notes:

* Uses `limit=250` pages until exhausted.
* Retries on 429/5xx with backoff; honors `Retry-After`/`X-Shopify-Shop-Api-Call-Limit`.
* Upserts:

    * `products` — `shopify_id`, `title`, `description`, `status`
    * `product_variants` — `sku`, `price`, `inventory_quantity`, options
    * `product_images` — `src`, `position`, `alt`

---

---

## 7) Minimal tests

### Run all

```bash
php artisan test
```

### Only the 3 required specs

* **OAuth callback**

    * File: `tests/Feature/OAuthCallbackTest.php`
    * Fakes `/admin/oauth/access_token`, verifies HMAC, asserts redirect + shop row saved with decrypted token.

```bash
php artisan test --filter=OAuthCallbackTest
```

* **Webhook verification**

    * File: `tests/Feature/WebhookVerificationTest.php`
    * Sends invalid HMAC → 401, valid HMAC → 204 and `ProcessProductWebhook` job dispatched.

```bash
php artisan test --filter=WebhookVerificationTest
```

* **Sync command**

    * File: `tests/Feature/SyncCommandTest.php`
    * Fakes Shopify `/products.json` paginated responses, runs `shopify:sync`, asserts products/variants/images upserted.

```bash
php artisan test --filter=SyncCommandTest
```
---


## 8) Commands to run

```bash
# run dev
php artisan serve
php artisan queue:work

# register/list webhooks
php artisan shopify:webhooks:register yourstore.myshopify.com
php artisan shopify:webhooks:list yourstore.myshopify.com

# sync now
php artisan shopify:sync yourstore.myshopify.com
```
 

 
