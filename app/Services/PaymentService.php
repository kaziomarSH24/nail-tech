<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Exception;
use Stripe\EphemeralKey;
use Stripe\PaymentIntent;
use Stripe\Stripe;

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
    public function initializePayment(Model $user, Model $payable, int $amount, array $customMetadata = [], string $currency = 'usd')
    {

        Stripe::setApiKey(config('cashier.secret'));

        // Ensure User is a Stripe Customer
        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer();
        }

        try {
            //Prepare Metadata (Merge Default + Custom)
            $defaultMetadata = [
                'payable_type' => get_class($payable),
                'payable_id'   => $payable->id,
                'user_id'      => $user->id,
            ];
            $finalMetadata = array_merge($defaultMetadata, $customMetadata);
            // Create Ephemeral Key (Required for Mobile App Security)
            $ephemeralKey = EphemeralKey::create(
                ['customer' => $user->stripe_id],
                ['stripe_version' => '2022-11-15'] // Use your Stripe Dashboard version
            );

            // Create Payment Intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => strtolower($currency),
                'customer' => $user->stripe_id,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => $finalMetadata,
            ]);

            return [
                'success' => true,
                'clientSecret' => $paymentIntent->client_secret, // Key for Frontend
                'ephemeralKey' => $ephemeralKey->secret,         // Key for App
                'customerId'   => $user->stripe_id,
                'publishableKey' => config('cashier.key'),
            ];
        } catch (Exception $e) {
            Log::error('Payment Init Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment initialization failed: ' . $e->getMessage()
            ];
        }
    }
}
