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
            'duration' => $this->duration,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'lessons_count' => $this->when($this->lessons_count !== null, $this->lessons_count),
            'lessons' => LessonResource::collection($this->whenLoaded('lessons')),
        ];
    }
}
