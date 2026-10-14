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
            'section_name' => $this->section_name ?? 'General',
            'lesson_type' => $this->lesson_type ?? 'video',
            'video_type' => $this->video_type,
            'video_path' => $this->video_path,
            'image_path' => $this->image_path,
            'duration' => $this->duration,
            'actual_duration' => $this->formatDuration(),
            'sort_order' => $this->sort_order,
            'is_free_preview' => $this->is_free_preview,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'video_url' => $this->video_url ? url($this->video_url) : null,
            'image_url' => $this->image_url ? url($this->image_url) : null,
        ];
    }

    private function formatDuration()
{
    $totalSeconds = $this->duration;

    if (!$totalSeconds) return '0s';

    $hours = floor($totalSeconds / 3600);
    $minutes = floor(($totalSeconds % 3600) / 60);
    $seconds = $totalSeconds % 60;
    if ($hours > 0) {
        return "{$hours}h {$minutes}m {$seconds}s";
    }
    if ($minutes > 0) {
        return "{$minutes}m {$seconds}s";
    }
    
    return "{$seconds}s";
}
}
