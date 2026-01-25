<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->user_id,
            'course_id'  => $this->course_id,
            'course'     => new CourseResource($this->whenLoaded('course')),
            'status'     => $this->status,
            'enrolled_at'=> $this->enrolled_at,
            'created_at' => $this->created_at,
        ];
    }
}
