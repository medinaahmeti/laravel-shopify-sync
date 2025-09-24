<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessProductWebhook;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function productCreate(Request $request)
    {
        $this->verifyWebhook($request);
        ProcessProductWebhook::dispatch(
            $request->header('X-Shopify-Shop-Domain'),
            'create',
            $request->getContent()
        );
        return response('', Response::HTTP_NO_CONTENT);
    }

    public function productUpdate(Request $request)
    {
        $this->verifyWebhook($request);
        ProcessProductWebhook::dispatch(
            $request->header('X-Shopify-Shop-Domain'),
            'update',
            $request->getContent()
        );
        return response('', Response::HTTP_NO_CONTENT);
    }

    public function productDelete(Request $request)
    {
        $this->verifyWebhook($request);
        ProcessProductWebhook::dispatch(
            $request->header('X-Shopify-Shop-Domain'),
            'delete',
            $request->getContent()
        );
        return response('', Response::HTTP_NO_CONTENT);
    }

    private function verifyWebhook(Request $request): void
    {
        // WHY: reject spoofed calls
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256', '');
        $computed = base64_encode(hash_hmac('sha256', $request->getContent(), config('services.shopify.secret'), true));
        abort_unless(hash_equals($computed, $hmacHeader), 401);
    }
}
