<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;
    protected $order;

    /**
     * Create a new notification instance.
     */
    /**
     * Create a new notification instance.
     */
    public function __construct($order)
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
    public function toArray(object $notifiable): array
    {
        $storeName = env('SHOPIFY_STORE_SLUG', 'super-shop-66115');
        $shopifyOrderId = $this->order->shopify_order_id;

        return [
            'title'   => 'New Shopify Order',
            'message' => 'Order #' . $this->order->shopify_order_id . ' received from Shopify.',
            'icon'    => 'shopping-bag', 
            'type'    => 'order',         
            'action'  => [
                'link'        => "https://admin.shopify.com/store/{$storeName}/orders/{$shopifyOrderId}",
                'is_external' => true
            ],
            'meta'    => [
                'amount'   => $this->order->total_price,
                'order_id' => $this->order->id
            ]
        ];
    }
}
