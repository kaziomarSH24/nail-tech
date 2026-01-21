<?php

namespace App\Listeners;

use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Admin\ShopifyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Cashier\Events\WebhookReceived;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\SetupIntent;
use Stripe\PaymentMethod;
use Exception;
use Throwable;

class StripeEventListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    /**
     * Handles incoming Stripe webhook events.
     */
    public function handle(WebhookReceived $event): void
    {
        $method = 'handle' . Str::studly(str_replace('.', '_', $event->payload['type']));

        Log::debug("Stripe webhook received. Event: {$event->payload['type']}. Attempting to call method: {$method}");

        if (method_exists($this, $method)) {
            $this->{$method}($event->payload);
        } else {
            Log::info("Method {$method} does not exist in StripeEventListener. Skipping.");
        }
    }

    /**
     * Handle Checkout Session Completed
     * (Supports both 'payment' for courses and 'setup' for saving cards)
     */
    /**
     * Handle Checkout Session Completed
     */
    protected function handleCheckoutSessionCompleted(array $payload): void
    {
        $session = $payload['data']['object'];
        $mode = $session['mode'] ?? 'unknown';
        $metadata = $session['metadata'] ?? [];

        Log::info("Stripe Checkout Completed. Mode: {$mode}", ['id' => $session['id']]);
        Log::info("Checkout Session Metadata: " . json_encode($metadata));


        Log::info("Checkout Session Metadata single: " . $metadata['type']);

        // 1. Handle Setup Mode (Save Card)
        if ($mode === 'setup') {
            $this->handleSetupMode($session);
            return;
        }

        // 2. Handle Payment Mode (Course Purchase)
        if ($mode === 'payment') {
            $this->logTransaction($session, $metadata);

            if (($metadata['type'] ?? '') === 'course_purchase') {
                $this->enrollUserInCourse($metadata);
            }
        }
    }

    /**
     * Handle Payment Intent Succeeded
     */
    protected function handlePaymentIntentSucceeded(array $payload): void
    {
        $intent = $payload['data']['object'];
        $metadata = $intent['metadata'] ?? [];

        // Log transaction
        $this->logTransaction($intent, $metadata);
        $type = $metadata['type'] ?? '';
        // Enroll User based on Type
        if ($type === 'course_purchase') {
            $this->enrollUserInCourse($metadata);
        } elseif ($type === 'shopify_purchase') {
            $this->processShopifyOrder($metadata);
        }
    }

    // --- Helper Methods ---

    private function handleSetupMode($session)
    {
        try {
            $user = User::where('stripe_id', $session['customer'])->first();
            if (!$user) return;

            Stripe::setApiKey(config('cashier.secret'));
            $setupIntent = SetupIntent::retrieve($session['setup_intent']);
            $pm = PaymentMethod::retrieve($setupIntent->payment_method);

            $fingerprint = $pm->card->fingerprint ?? null;

            if ($fingerprint) {
                foreach ($user->paymentMethods() as $method) {
                    if (($method->card->fingerprint ?? '') === $fingerprint) {
                        Log::info('Duplicate card ignored.');
                        return;
                    }
                }
            }

            $user->addPaymentMethod($setupIntent->payment_method);

            if (!$user->hasDefaultPaymentMethod()) {
                $user->updateDefaultPaymentMethod($setupIntent->payment_method);
            }
        } catch (Throwable $e) {
            Log::error("Setup Mode Error: " . $e->getMessage());
        }
    }

    private function logTransaction($data, $metadata)
    {
        try {
            $userId = $metadata['user_id'] ?? null;
            if (!$userId) {
                Log::warning("Transaction skipped: No user_id in metadata");
                return;
            }

            $amountRaw = $data['amount_received'] ?? $data['amount_total'] ?? 0;
            $amount = $amountRaw / 100;

            Transaction::create([
                'user_id' => $userId,
                'payable_type' => $metadata['payable_type'] ?? null,
                'payable_id' => $metadata['payable_id'] ?? null,
                'gateway' => 'stripe',
                'gateway_transaction_id' => $data['id'],
                'amount' => $amount,
                'currency' => $data['currency'] ?? 'usd',
                'status' => 'success',
                'metadata' => $data, // Store full stripe object as JSON
            ]);

            Log::info("Transaction created for User {$userId}");
        } catch (Throwable $e) {
            Log::error('Failed to log transaction: ' . $e->getMessage());
        }
    }

    private function enrollUserInCourse($metadata)
    {
        try {
            $userId = $metadata['user_id'] ?? null;
            $courseId = $metadata['payable_id'] ?? null;

            if ($userId && $courseId) {
                $enrollment = Enrollment::firstOrCreate(
                    ['user_id' => $userId, 'course_id' => $courseId],
                    ['progress_percent' => 0, 'completed' => false]
                );

                if ($enrollment->wasRecentlyCreated) {
                    Log::info("NEW Enrollment created for User {$userId} in Course {$courseId}");
                } else {
                    Log::info("User {$userId} was ALREADY enrolled in Course {$courseId}");
                }
            } else {
                Log::warning("Enrollment skipped: Missing user_id or payable_id in metadata", $metadata);
            }
        } catch (Throwable $e) {
            Log::error('Failed to enroll user: ' . $e->getMessage());
        }
    }

    /**
     * Helper Method to Handle Shopify Order
     */
    private function processShopifyOrder(array $metadata): void
    {
        $orderId = $metadata['order_id'] ?? null;

        if (!$orderId) {
            \Log::error('Stripe Webhook: Order ID missing in metadata for shopify_purchase.');
            return;
        }
        $order = Order::find($orderId);
        if ($order && $order->status === 'pending') {

            try {
                $order->update(['status' => 'paid']);
                $shopifyService = app(ShopifyService::class);
                $shopifyService->syncOrderToShopify($order);

                \Log::info("Shopify Order Sync Successful for Local Order #{$order->id}");
            } catch (\Exception $e) {
                \Log::error("Shopify Order Sync Failed for Local Order #{$order->id}: " . $e->getMessage());
            }
        }
    }
}
