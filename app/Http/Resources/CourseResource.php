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
            'is_enrolled' => (bool) ($this->is_enrolled ?? false),
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

    private function formatDuration()
    {


        $totalSeconds = $this->lessons_sum_duration ?? $this->lessons->sum('duration');

        if (!$totalSeconds) return '0m';
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }
}
