<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\Admin\ShopifyService;
use App\Services\PaymentService;

class ShopController extends Controller
{
    protected PaymentService $paymentService;
    protected ShopifyService $shopifyService;

    public function __construct(PaymentService $paymentService, ShopifyService $shopifyService)
    {
        $this->paymentService = $paymentService;
        $this->shopifyService = $shopifyService;
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

       
        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'delivery_status' => 'unfulfilled', 
            'total_amount' => $totalAmount,
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
            'payment_details' => $paymentData
        ]);
    }
}
