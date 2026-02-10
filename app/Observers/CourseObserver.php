<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Course;
use App\Notifications\NewCourseAvailable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

class CourseObserver extends BaseObserver
{
    protected array $relatedCacheTags = ['lessons'];


    public function created(Model $course): void
    {
        parent::created($course);
        if (!$course instanceof Course) {
            return;
        }
        User::query()
            ->where('notification_settings->new_courses', true)
            ->orWhereNull('notification_settings')
            ->chunk(100, function ($users) use ($course) {
                Notification::send($users, new NewCourseAvailable($course));
            });
    }
}
