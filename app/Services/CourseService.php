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
        $hasAccess = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->exists();

        return $hasAccess;
    }

    // Define allowed sorts
    protected function getAllowedSorts(): array
    {
        return ['id', 'price', 'created_at', 'title'];
    }


    public function addLesson(int|string $courseId, array $data): Model
    {
        $course = $this->getById($courseId);

        $lessonType = $data['lesson_type'] ?? 'video';

        // Path logic resolve
        $finalPath = null;
        if ($lessonType === 'video' || !empty($data['video_type'])) {
            $finalPath = (($data['video_type'] ?? 'upload') === 'upload')
                ? ($data['video_path'] ?? null)
                : ($data['video_url'] ?? null);
        }

        return Lesson::create([
            'course_id' => $course->id,
            'section_name' => !empty($data['section_name']) ? trim($data['section_name']) : 'General',
            'lesson_type' => $lessonType,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'image_path' => $data['image_path'] ?? null,
            'video_type' => $data['video_type'] ?? 'upload',
            'video_path' => $finalPath,
            'duration' => $data['duration'] ?? 0,
            'sort_order' => $data['sort_order'] ?? ($course->lessons()->count() + 1),
            'is_free_preview' => $data['is_free_preview'] ?? false,
        ]);
    }


    /**
     * Get Popular Courses
     */
    public function getPopularCourses($limit = 5, $paginate = false)
    {
        $query = Course::where('is_published', true)
            ->withSum('lessons', 'duration')
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
            ->withSum('lessons', 'duration')
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
