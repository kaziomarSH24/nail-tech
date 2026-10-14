<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
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
            'title' => $this->title,
            'instructor' => $this->instructor,
            'description' => $this->description,
            'thumbnail' => url('storage/' . $this->thumbnail),
            'price' => $this->price,
            'reward_points' => $this->reward_points,
            'is_published' => $this->is_published,
            'is_enrolled' => (bool) ($this->is_enrolled),
            'duration' => $this->duration,
            'actual_duration' => $this->formatDuration(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'total_lessons' => $this->lessons_count ?? $this->lessons->count(),
            'progress_percent' => $this->when(isset($this->progress_percent), $this->progress_percent),
            'is_completed' => $this->when(isset($this->is_completed), $this->is_completed),
            'enrolled_at' => $this->when(isset($this->enrolled_at), $this->enrolled_at),
            'lessons' => LessonResource::collection($this->whenLoaded('lessons')),
        ];
    }

    private function formatDuration(): ?string
    {
        $totalSeconds = (int) ($this->lessons_sum_duration ?? (isset($this->lessons) ? $this->lessons->sum('duration') : 0));

        if ($totalSeconds <= 0) {
            return null;
        }

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = floor($totalSeconds % 60);

        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }
        if ($minutes > 0) {
            return $seconds > 0 ? "{$minutes}m {$seconds}s" : "{$minutes}m";
        }

        return "{$seconds}s";
    }
}
