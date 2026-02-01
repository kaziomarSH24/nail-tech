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

        $taxRate = (float) \App\Models\Setting::getValue('tax_percentage', 0);
        $shippingCost = (float) \App\Models\Setting::getValue('shipping_cost', 0);

        $customerInfo = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->shipping_address,
        ];

        $subTotal = 0;
        $processedItems = [];

        foreach ($request->items as $item) {
            $product = Product::where('shopify_variant_id', $item['variant_id'])->first();

            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            if ($product->stock_quantity < $item['quantity']) {
                return response()->json(['message' => 'Out of stock'], 400);
            }

            $subTotal += $product->price * $item['quantity'];

            $processedItems[] = [
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
                'title' => $product->title,
                'price' => $product->price,
            ];
        }

        $discountAmount = 0;
        $pointsToRedeem = $request->points_to_redeem ?? 0;

        if ($pointsToRedeem > 0) {
            try {
                $redemptionData = $this->rewardService->calculateRedemptionLogic(
                    $user,
                    $pointsToRedeem,
                    $subTotal
                );

                $discountAmount = $redemptionData['discount_amount'];
                $pointsToRedeem = $redemptionData['points_used'];
            } catch (\Exception $e) {
                return response_error($e->getMessage(), [], 400);
            }
        }

        $taxableAmount = max($subTotal - $discountAmount, 0);
        $taxAmount = ($taxableAmount * $taxRate) / 100;
        $finalAmount = $taxableAmount + $taxAmount + $shippingCost;

        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'sub_total' => $subTotal,
            'discount_amount' => $discountAmount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'shipping_cost' => $shippingCost,
            'final_amount' => $finalAmount,
            'redeemed_points' => $pointsToRedeem,
            'items' => $processedItems,
            'shipping_address' => $customerInfo,
        ]);

        $paymentData = $this->paymentService->initializePayment(
            $user,
            $order,
            (int) ($finalAmount * 100),
            [
                'order_id' => $order->id,
                'type' => 'shopify_purchase',
            ]
        );

        if (!$paymentData['success']) {
            return response_error($paymentData['message'], [], 500);
        }

        return response_success('Checkout initialized.', [
            'order_id' => $order->id,
            'summary' => [
                'sub_total' => round($subTotal, 2),
                'discount_amount' => round($discountAmount, 2),
                'taxable_amount' => round($taxableAmount, 2),
                'tax_rate' => $taxRate . '%',
                'tax_amount' => round($taxAmount, 2),
                'shipping_cost' => round($shippingCost, 2),
                'total_payable' => round($finalAmount, 2),
                'redeemed_points' => (int) $pointsToRedeem,
            ],
            'payment_details' => $paymentData,
        ]);
    }
}
