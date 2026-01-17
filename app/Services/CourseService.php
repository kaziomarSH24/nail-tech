<?php

namespace App\Services;

use App\Services\BaseService;
use App\Models\Course;
use Spatie\QueryBuilder\AllowedFilter;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Model;

class CourseService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = Course::class;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }


    // Define allowed filters
    protected function getAllowedFilters(): array
    {
        return [
            'title',
            AllowedFilter::exact('is_published'),
            AllowedFilter::exact('source'), // local or wix
        ];
    }

    // Define allowed includes relationships
    protected function getAllowedIncludes(): array
    {
        return [
            'lessons',
        ];
    }


    // public function hasAccessToCourse($user, $courseId): bool
    // {
    //     // Option 1: Using 'Enrollment' table (Best Practice)
    //     // Check if user has an active enrollment record
    //     $hasEnrollment = Enrollment::where('user_id', $user->id)
    //         ->where('course_id', $courseId)
    //         ->exists();

    //     if ($hasEnrollment) {
    //         return true;
    //     }

    //     // Option 2: Using 'Order' table (Alternative)
    //     // Check if user has a completed order for this course
    //     // $hasPurchased = \App\Models\Order::where('user_id', $user->id)
    //     //     ->where('status', 'completed')
    //     //     ->whereHas('items', function ($query) use ($courseId) {
    //     //         $query->where('course_id', $courseId);
    //     //     })->exists();

    //     // return $hasPurchased;

    //     return false;
    // }

    // Define allowed sorts
    protected function getAllowedSorts(): array
    {
         return ['id', 'price', 'created_at', 'title'];
    }


    public function addLesson(int|string $courseId, array $data): Model
    {
        $course = $this->getById($courseId);

        // Path logic resolve
        $finalPath = ($data['video_type'] === 'upload') ? ($data['video_path'] ?? null) : ($data['video_url'] ?? null);

        return Lesson::create([
            'course_id' => $course->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'video_type' => $data['video_type'],
            'video_path' => $finalPath,
            'duration_minutes' => $data['duration'] ?? 0,
            'sort_order' => $course->lessons()->count() + 1,
            'is_free_preview' => $data['is_free_preview'] ?? false,
        ]);
    }
}
