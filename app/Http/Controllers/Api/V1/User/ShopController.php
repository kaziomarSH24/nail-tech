<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\Admin\ShopifyService;
use App\Services\PaymentService;
use App\Services\RewardService;

class ShopController extends Controller
{
    protected PaymentService $paymentService;
    protected ShopifyService $shopifyService;
    protected RewardService $rewardService;

    public function __construct(PaymentService $paymentService, ShopifyService $shopifyService, RewardService $rewardService)
    {
        $this->paymentService = $paymentService;
        $this->shopifyService = $shopifyService;
        $this->rewardService = $rewardService;
    }

    public function initiateCheckout(CheckoutRequest $request)
    {
        $user = auth()->user();

        $customerInfo = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->shipping_address,
        ];

        $totalAmount = 0;
        $processedItems = [];


        foreach ($request->items as $item) {
            $product = Product::where('shopify_variant_id', $item['variant_id'])->first();


            if (!$product) {
                return response()->json(['message' => "Product not found (Variant ID: {$item['variant_id']})"], 404);
            }


            if ($product->stock_quantity < $item['quantity']) {
                return response()->json([
                    'ok' => false,
                    'message' => "Out of stock: {$product->title}",
                    'available_stock' => $product->stock_quantity
                ], 400);
            }

            $totalAmount += $product->price * $item['quantity'];

            $processedItems[] = [
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
                'title' => $product->title,
                'product_id' => $product->id,
                'shopify_product_id' => $product->shopify_product_id,
                'image_url' => $product->image_url,
                'price' => $product->price
            ];
        }

        // --- Redemption Logic Start ---
        $discountAmount = 0;
        $pointsToRedeem = $request->points_to_redeem ?? 0;

        if ($pointsToRedeem > 0) {
            try {
                // SAME FUNCTION CALL
                $redemptionData = $this->rewardService->calculateRedemptionLogic(
                    $user,
                    $pointsToRedeem,
                    $totalAmount
                );
                $discountAmount = $redemptionData['discount_amount'];
                $pointsToRedeem = $redemptionData['points_used'];
            } catch (\Exception $e) {
                return response_error($e->getMessage(), [], 400);
            }
        }

        $finalAmount = $totalAmount - $discountAmount;
        // --- Redemption Logic End ---


        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'delivery_status' => 'unfulfilled',
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'redeemed_points' => $pointsToRedeem, 
            'final_amount' => $finalAmount,
            'items' => $processedItems,
            'shipping_address' => $customerInfo,
        ]);


        $paymentData = $this->paymentService->initializePayment(
            $user,
            $order,
            (int) ($totalAmount * 100),
            [
                'order_id' => $order->id,
                'type' => 'shopify_purchase'
            ]
        );

        if (!$paymentData['success']) {
            return response_error($paymentData['message'], [], 500);
        }

        return response_success('Checkout initialized.', [
            'order_id' => $order->id,
            'summary' => [
                'subtotal' => (float) $totalAmount,
                'discount_amount' => (float) $discountAmount,
                'redeemed_points' => (int) $pointsToRedeem,
                'total_payable' => (float) $finalAmount,
                'message' => "Redeemed {$pointsToRedeem} points for $" . $discountAmount . " off!"
            ],
            'payment_details' => $paymentData
        ]);
    }
}
