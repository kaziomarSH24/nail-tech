<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'dob'        => $this->dob,
            'avatar'     => $this->avatar_url,
            'status'     => $this->status,
            'address'    => $this->address,
            'is_suspended' => $this->is_suspended,
            'created_at' => $this->created_at,
            'appointments' => AppointmentResource::collection($this->whenLoaded('appointments')),
            'enrollments' => EnrollmentResource::collection($this->whenLoaded('enrollments')),
        ];
    }
}
