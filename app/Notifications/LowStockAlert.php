<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected $inventoryItem;

    public function __construct($inventoryItem)
    {
        $this->inventoryItem = $inventoryItem;
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
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
        'title'   => 'Low Stock Alert!',
        'message' => "Only {$this->inventoryItem->current_quantity} left for '{$this->inventoryItem->item_name}'.",
        'icon'    => 'alert-triangle',
        'type'    => 'low_stock',
        'action'  => [
            'link'        => '/admin/inventory/' . $this->inventoryItem->id,
            'is_external' => false
        ],
        'meta'    => [
            'inventory_id' => $this->inventoryItem->id
        ]
    ];
    }
}
