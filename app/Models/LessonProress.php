<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonProress extends Model
{
    protected $guarded = [];
    protected $table = 'lesson_proresses';


    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with Lesson
     */
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Relationship with Course
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
