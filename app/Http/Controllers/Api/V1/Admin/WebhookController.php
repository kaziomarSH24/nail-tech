<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\OrderShippedNotification;
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

    public function handleOrderUpdate(Request $request)
    {
        $secret = env('SHOPIFY_WEBHOOK_SECRET');
        $hmacHeader = $request->header('x-shopify-hmac-sha256');
        $data = $request->getContent();

        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, $secret, true));

        if (!hash_equals($hmacHeader, $calculatedHmac)) {
            Log::warning('Shopify Order Webhook Verification Failed!');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $payload = json_decode($data, true); // JSON Decode

            Log::info('Webhook Hit for Order ID: ' . ($payload['id'] ?? 'Unknown'));
            $shopifyOrderId = $payload['id'];

            $order = Order::where('shopify_order_id', $shopifyOrderId)->first();

            if ($order) {
                $fulfillmentStatus = $payload['fulfillment_status'];

                Log::info("Processing Order #{$order->id}: Fulfillment Status - {$fulfillmentStatus}");
                $trackingCompany = null;
                $trackingNumber = null;
                $trackingUrl = null;

                if (!empty($payload['fulfillments'])) {

                    $latestFulfillment = $payload['fulfillments'][0];

                    $trackingCompany = $latestFulfillment['tracking_company'] ?? null;
                    $trackingNumber = $latestFulfillment['tracking_number'] ?? null;
                    $trackingUrl = $latestFulfillment['tracking_url'] ?? null;
                }

                if ($fulfillmentStatus === 'fulfilled' && $order->delivery_status !== 'shipped') {

                    $order->update([
                        'delivery_status' => 'shipped',
                        'tracking_company' => $trackingCompany,
                        'tracking_number' => $trackingNumber,
                        'tracking_url' => $trackingUrl,
                    ]);
                    if ($order->user) {
                        try {
                            
                            $order->user->notify(new OrderShippedNotification($order));
                            Log::info("Notification sent to user {$order->user->id}");
                        } catch (\Exception $e) {
                            Log::error("Notification Failed: " . $e->getMessage());
                        }
                    }
                    //partially_fulfilled
                } elseif ($fulfillmentStatus === 'partial') {
                    $order->delivery_status = 'partial';
                } elseif ($fulfillmentStatus === null) {
                    $order->delivery_status = 'unfulfilled';
                }
                $financialStatus = $payload['financial_status'];

                if ($financialStatus === 'refunded') {
                    $order->status = 'refunded';
                }

                $order->save();

                Log::info("Order #{$order->id} updated. Delivery: {$order->delivery_status}");
            }

            return response()->json(['message' => 'Processed']);
        } catch (\Exception $e) {
            Log::error('Order Webhook Failed: ' . $e->getMessage());
            return response()->json(['message' => 'Error'], 500);
        }
    }
}
