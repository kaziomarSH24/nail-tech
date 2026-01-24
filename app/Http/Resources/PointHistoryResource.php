<?php

namespace App\Http\Resources;

use App\Models\Enrollment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->description,
            'points' => (int) $this->points,
            'type' => $this->type, // 'earn' or 'redeem'
            'date' => $this->created_at->diffForHumans(),
            'created_at' => $this->created_at,
            'source' => $this->getSourceType(),
        ];
    }

    private function getSourceType()
    {
        if ($this->rewardable_type === Order::class) return 'product';
        if ($this->rewardable_type === Enrollment::class) return 'course';
        return 'other';
    }
}
