<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected $shopifyService;

    public function __construct(ShopifyService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
    }

    public function handleProductWebhook(Request $request)
    {

        $secret = env('SHOPIFY_WEBHOOK_SECRET');
        $hmacHeader = $request->header('x-shopify-hmac-sha256');
        $data = $request->getContent();
        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, $secret, true));
        if (!hash_equals($hmacHeader, $calculatedHmac)) {
            Log::warning('Shopify Webhook Verification Failed!');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $payload = json_decode($data, true); // JSON Decode

            Log::info('Webhook Hit for Product: ' . ($payload['title'] ?? 'Unknown'));

            $this->shopifyService->saveProduct($payload);

            Cache::tags('products')->flush();
            return response()->json(['message' => 'Received'], 200);
        } catch (\Exception $e) {
            Log::error('Webhook Failed: ' . $e->getMessage());
            return response()->json(['message' => 'Error'], 500);
        }
    }
}
