<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{

    use Sluggable;
    protected $guarded = [];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }
    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('created_at', 'asc');
    }

    //relationship with Enrollment
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }


    public function scopeWithEnrollmentStatus($query)
    {
        $userId = auth('sanctum')->id();
        return $query->when($userId, function ($q) use ($userId) {
            $q->withExists(['enrollments as is_enrolled' => function ($subQuery) use ($userId) {
                $subQuery->where('user_id', $userId);
            }]);
        });
    }
}
