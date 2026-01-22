<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderShippedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    protected $order;
    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //         ->line('The introduction to the notification.')
    //         ->action('Notification Action', url('/'))
    //         ->line('Thank you for using our application!');
    // }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->order->id,
            'title' => 'Order Shipped! 🚚',
            'message' => "Your order #{$this->order->id} has been shipped via {$this->order->tracking_company}.",
            'tracking_number' => $this->order->tracking_number,
        ];
    }

    public function toFirebase(object $notifiable): array
    {
        $tokens = $notifiable->fcmTokens->pluck('token')->toArray();
        if (empty($tokens)) {
            return [];
        }

        $courierName = $this->order->tracking_company ? " via {$this->order->tracking_company}" : "";

        return [
            'tokens' => $tokens,

            'title' => 'Order Shipped! 🚚',
            'body' => "Good news! Your order #{$this->order->id} has been shipped{$courierName}. Track it now.",

            'data' => [
                'type' => 'order_shipped',
                'order_id' => (string) $this->order->id,
                'shopify_order_id' => (string) $this->order->shopify_order_id,
                'status' => 'shipped',
                'tracking_number' => (string) ($this->order->tracking_number ?? ''),
                'tracking_url' => (string) ($this->order->tracking_url ?? ''),
            ],
        ];
    }
}
