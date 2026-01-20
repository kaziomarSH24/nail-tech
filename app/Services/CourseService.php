<?php

namespace App\Services;

use App\Services\BaseService;
use App\Models\Course;
use App\Models\Enrollment;
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
    protected bool $cachePerUser = true;

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


    public function hasAccessToCourse($user, $courseId): bool
    {
        // Check if user has an active enrollment record
        $hasEnrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->exists();

        if ($hasEnrollment) {
            return true;
        }
        return false;
    }

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
            'duration' => $data['duration'] ?? 0,
            'sort_order' => $course->lessons()->count() + 1,
            'is_free_preview' => $data['is_free_preview'] ?? false,
        ]);
    }


        /**
     * Get Popular Courses
     */
    public function getPopularCourses($limit = 5, $paginate = false)
    {
        $query = Course::where('is_published', true)
            ->withCount('enrollments')
            ->orderBy('enrollments_count', 'desc');

        if ($paginate) {
            return $query->paginate($limit);
        }

        return $query->take($limit)->get();
    }

    /**
     * Get Trending Courses
     */
    public function getTrendingCourses($limit = 5, $paginate = false)
    {
        $query = Course::where('is_published', true)
            ->withCount(['enrollments' => function ($q) {
                // count only enrollments in last 30 days
                $q->where('created_at', '>=', now()->subDays(30));
            }])
            ->orderBy('enrollments_count', 'desc');

        if ($paginate) {
            return $query->paginate($limit);
        }

        return $query->take($limit)->get();
    }

}
