 <?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\WebhookController;

Route::get('/install', [OAuthController::class, 'install'])->name('shopify.install');
Route::get('/oauth/callback', [OAuthController::class, 'callback'])->name('shopify.callback');

Route::post('/webhooks/products/create', [WebhookController::class, 'productCreate']);
Route::post('/webhooks/products/update', [WebhookController::class, 'productUpdate']);
Route::post('/webhooks/products/delete', [WebhookController::class, 'productDelete']);

Route::view('/installed', 'installed');
