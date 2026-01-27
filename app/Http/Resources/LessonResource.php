<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
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
            'course_id' => $this->course_id,
            'title' => $this->title,
            'description' => $this->description,
            'video_type' => $this->video_type,
            'video_path' => $this->video_path,
            'duration' => $this->duration,
            'actual_duration' => $this->formatDuration(),
            'sort_order' => $this->sort_order,
            'is_free_preview' => $this->is_free_preview,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'video_url' => url($this->video_url),
        ];
    }

    private function formatDuration()
    {


        $totalSeconds = $this->duration;

        if (!$totalSeconds) return '0m';
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }
}
