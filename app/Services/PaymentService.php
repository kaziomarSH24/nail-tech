<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Exception;


class PaymentService
{
    /**
     * Create a Stripe Checkout Session (Redirect Method)
     * Suitable for: Subscriptions, One-time purchases where you want Stripe to handle the UI.
     * * @param Model $user The user making the payment.
     * @param Model $payable The item being purchased (e.g., Course, Product).
     * @param int $amount Amount in cents (e.g., $10.00 = 1000).
     * @param string $description Description for the receipt/checkout page.
     * @param string $currency Currency code (default 'usd').
     */
    public function createCheckoutSession(Model $user, Model $payable, int $amount, string $description, string $currency = 'usd')
    {
        $metadata = [
            'payable_type' => get_class($payable),
            'payable_id'   => $payable->id,
            'user_id'      => $user->id,
            'type'         => 'course_purchase'
        ];

        $checkoutSession = $user->checkout(
            [],
            [
                'mode' => 'payment',
                'success_url' => config('app.frontend_url') . '/payment/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.frontend_url') . '/payment/cancelled',
                'metadata' => $metadata,
                'payment_intent_data' => [
                    'metadata' => $metadata
                ],

                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($currency),
                        'product_data' => [
                            'name' => $description,
                        ],
                        'unit_amount' => $amount,
                    ],
                    'quantity' => 1,
                ]],
            ]
        );

        return [
            'success' => true,
            'redirect_url' => $checkoutSession->url
        ];
    }

    /**
     * Create a Payment Intent (Custom UI Method)
     * Suitable for: When you build your own card input form using Stripe Elements in React/Flutter.
     * * @param Model $user The user making the payment.
     * @param Model $payable The item being purchased.
     * @param int $amount Amount in cents.
     * @param string $paymentMethodId The ID from Stripe frontend (pm_...).
     * @param string $currency Currency code.
     */
    public function createPaymentIntent(Model $user, Model $payable, int $amount, string $paymentMethodId, string $currency = 'usd')
    {
        try {
            // Charge the user using Cashier
            $payment = $user->charge(
                $amount,
                $paymentMethodId,
                [
                    'currency' => strtolower($currency),
                    'metadata' => [
                        'payable_type' => get_class($payable),
                        'payable_id'   => $payable->id,
                        'user_id'      => $user->id,
                        'type'         => 'course_purchase_intent'
                    ],
                    // Return URL is required for 3D Secure redirects
                    'return_url' => config('app.frontend_url') . '/payment/return',
                ]
            );

            return [
                'success' => true,
                'payment' => $payment,
            ];
        } catch (IncompletePayment $e) {
            return [
                'success' => true, 
                'requires_action' => true,
                'client_secret' => $e->payment->client_secret,
            ];
        } catch (Exception $e) {
            Log::error('Stripe Payment Intent Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not process payment. ' . $e->getMessage()];
        }
    }
}
