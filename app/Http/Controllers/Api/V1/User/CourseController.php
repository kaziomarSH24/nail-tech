<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\CourseService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use App\Models\LessonProgress;

class CourseController extends Controller
{
    protected CourseService $courseService;
    protected PaymentService $paymentService; // Property

    // Inject PaymentService
    public function __construct(CourseService $courseService, PaymentService $paymentService)
    {
        $this->courseService = $courseService;
        $this->paymentService = $paymentService;
    }


    public function getEnrolledCourses(Request $request)
    {
        $user = auth()->user();
        $limit = $request->input('per_page', 10);

        // Fetch enrolled courses through Enrollment model with pagination
        $enrollments = Enrollment::with(['course.lessons' => function ($q) {
            $q->select('id', 'course_id', 'title', 'duration', 'is_free_preview');
        }])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($limit);

        // Transform the collection to return a clean structure
        $courses = $enrollments->map(function ($enrollment) {
            $course = $enrollment->course;
            if ($course) {

                $course->progress_percent = $enrollment->progress_percent;
                $course->is_completed = $enrollment->completed;
                $course->enrolled_at = $enrollment->created_at;
            }
            return $course;
        })->filter();

        return response()->json([
            'ok' => true,
            'message' => 'Enrolled courses retrieved successfully.',
            'data' => CourseResource::collection($courses),
            'pagination' => [
                'total' => $enrollments->total(),
                'per_page' => $enrollments->perPage(),
                'current_page' => $enrollments->currentPage(),
                'last_page' => $enrollments->lastPage(),
                'from' => $enrollments->firstItem(),
                'to' => $enrollments->lastItem(),
            ],
        ]);
    }

    /**
     * Mark Lesson as Completed & Update Progress
     */
    public function completeLesson(Request $request, $courseId, $lessonId)
    {
        $user = auth()->user();
        $course = Course::findOrFail($courseId);

        // Mark lesson as complete using Model
        LessonProgress::updateOrInsert(
            [
                'user_id' => $user->id,
                'lesson_id' => $lessonId,
                'course_id' => $courseId
            ],
            [
                'completed_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Calculate Progress using Model
        $totalLessons = $course->lessons()->count();
        $completedLessons = LessonProgress::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->count();

        $percent = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        // Update Enrollment Table
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if ($enrollment) {
            $enrollment->update([
                'progress_percent' => $percent,
                'completed' => $percent === 100
            ]);
        }

        return response_success('Lesson completed.', [
            'progress' => $percent,
            'is_course_completed' => $percent === 100
        ]);
    }

    /**
     * Get All Published Courses
     */
    public function index()
    {
        $courses = Course::where('is_published', true)->latest()->paginate(10);
        return CourseResource::collection($courses)->additional([
            'ok' => true,
            'message' => 'Courses retrieved successfully.'
        ]);
    }

    /**
     * Get Single Course Details
     */
    public function show($id)
    {
        $course = Course::with(['lessons' => function ($q) {
            $q->where('is_free_preview', true);
        }])->where('is_published', true)->findOrFail($id);

        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            if ($this->courseService->hasAccessToCourse($user, $course->id)) {
                $course->load('lessons');
            }
        }

        return response_success('Course details fetched.', new CourseResource($course));
    }

    /**
     * Enroll in a Course
     */
    public function enroll(Request $request, $courseId)
    {
        $user = auth()->user();
        $course = Course::where('is_published', true)->findOrFail($courseId);

        //Check if already enrolled
        if ($this->courseService->hasAccessToCourse($user, $courseId)) {
            return response_error('You are already enrolled in this course.', [], 400);
        }

        //Handle Free Course
        if ($course->price <= 0) {
            Enrollment::firstOrCreate(
                ['user_id' => $user->id, 'course_id' => $course->id],
                ['progress_percent' => 0, 'completed' => false]
            );
            return response_success('Enrolled successfully in free course.', [], 201);
        }

        // Handle Paid Course
        $amountInCents = (int) ($course->price * 100);

        $paymentResult = $this->paymentService->initializePayment(
            $user,
            $course,
            $amountInCents,
            ['type' => 'course_purchase'] // Webhook will use this 'type'
        );

        if (!$paymentResult['success']) {
            return response_error($paymentResult['message'] ?? 'Payment init failed.', [], 500);
        }

        // Return Data to Frontend (App will use this to open Stripe Modal)
        return response_success('Payment initialized.', $paymentResult);
    }

    /**
     * Get Home Screen Data
     */
    public function getHomeData()
    {
        $user = auth()->user();

        // My Subscriptions
        $enrollments = Enrollment::with(['course.lessons' => function ($q) {
            $q->select('id', 'course_id', 'title', 'duration');
        }])
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        $myCourses = $enrollments->map(function ($enrollment) {
            $course = $enrollment->course;
            if ($course) {
                $course->progress_percent = $enrollment->progress_percent;
                $course->is_completed = $enrollment->completed;
                $course->enrolled_at = $enrollment->created_at;
            }
            return $course;
        })->filter();

        // Popular Courses
        $popularCourses = $this->courseService->getPopularCourses(5);

        // Trending Courses
        $trendingCourses = $this->courseService->getTrendingCourses(5);

        return response_success('Home data fetched successfully.', [
            'my_subscriptions' => CourseResource::collection($myCourses),
            'popular_courses'  => CourseResource::collection($popularCourses),
            'trending_courses' => CourseResource::collection($trendingCourses),
        ]);
    }

    /**
     * Get All Popular Courses with Pagination
     */
    public function getPopularCourses(Request $request)
    {
        $limit = $request->input('per_page', 10);
        $courses = $this->courseService->getPopularCourses($limit, true); // Paginate = true

        return CourseResource::collection($courses)->additional([
            'ok' => true,
            'message' => 'Popular courses retrieved successfully.'
        ]);
    }

    /**
     * Get All Trending Courses with Pagination (For 'See All' Page)
     */
    public function getTrendingCourses(Request $request)
    {
        $limit = $request->input('per_page', 10);
        $courses = $this->courseService->getTrendingCourses($limit, true); // Paginate = true

        return CourseResource::collection($courses)->additional([
            'ok' => true,
            'message' => 'Trending courses retrieved successfully.'
        ]);
    }
}
