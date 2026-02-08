<?php

namespace App\Notifications;

use App\Notifications\Channels\FirebaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RestockReminder extends Notification
{
    use Queueable;

    protected $inventoryItem;

    public function __construct($inventoryItem)
    {
        $this->inventoryItem = $inventoryItem;
    }

    public function via($notifiable)
    {
        return ['database', FirebaseChannel::class];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Restock Reminder',
            'message' => "It's time to restock '{$this->inventoryItem->item_name}'.",
            'inventory_id' => $this->inventoryItem->id,
            'icon' => 'refresh-cw',
            'type' => 'reminder'
        ];
    }

    // Firebase Notification Representation
    public function toFirebase($notifiable)
    {
        return [
            'title' => 'Restock Reminder',
            'body' => "It's time to restock '{$this->inventoryItem->item_name}'.",
            'data' => [
                'inventory_id' => (string) $this->inventoryItem->id,
                'type' => 'reminder',
                // 'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],

            'tokens' => $notifiable->deviceTokens->pluck('token')->toArray() ?? [],
        ];
    }
}
