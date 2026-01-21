<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
        $displayStatus = match ($this->delivery_status) {
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'returned' => 'Returned',
            'unfulfilled' => 'Processing',
            default => 'Pending',
        };
        $statusColor = match ($this->delivery_status) {
            'shipped', 'delivered' => '#28a745', // Green
            'returned' => '#dc3545', // Red
            default => '#ffc107', // Yellow
        };

        return [
            'id' => $this->id,
            'order_number' => '#' . str_pad($this->id, 6, '0', STR_PAD_LEFT), // e.g., #000123
            'shopify_order_id' => $this->shopify_order_id,

            'total_amount' => (float) $this->total_amount,
            'currency' => 'USD',

            // Statuses
            'payment_status' => ucfirst($this->status), // Paid / Pending
            'delivery_status' => $this->delivery_status,
            'display_status' => $displayStatus,
            'status_color' => $statusColor,

            // Date Formatting
            'date' => Carbon::parse($this->created_at)->format('d M Y, h:i A'),

            'item_count' => count($this->items ?? []),
            'items' => $this->formatItems($this->items),

            'shipping_address' => $this->shipping_address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }


    private function formatItems($items)
    {
        if (empty($items)) return [];
            
        return collect($items)->map(function ($item) {

            return [
                'variant_id' => $item['variant_id'] ?? null,
                'title' => $item['title'] ?? 'Unknown Product',
                'image_url' => $item['image_url'] ?? null,
                'quantity' => $item['quantity'],
                'price' => (float) ($item['price'] ?? 0),
            ];
        });
    }
}
