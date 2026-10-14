<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Services\CourseService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use App\Models\LessonProgress;
use App\Models\Setting;
use App\Services\RewardService;

class CourseController extends Controller
{
    protected CourseService $courseService;
    protected PaymentService $paymentService; // Property
    protected RewardService $rewardService; // Property

    // Inject PaymentService
    public function __construct(CourseService $courseService, PaymentService $paymentService, RewardService $rewardService)
    {
        $this->courseService = $courseService;
        $this->paymentService = $paymentService;
        $this->rewardService = $rewardService;
    }


    public function getEnrolledCourses(Request $request)
    {
        $user = auth()->user();
        $limit = $request->input('per_page', 10);

        // Fetch enrolled courses through Enrollment model with pagination
        $enrollments = Enrollment::with(['course.lessons' => function ($q) {
            $q->select('id', 'course_id', 'title', 'duration', 'is_free_preview', 'section_name', 'lesson_type', 'image_path', 'sort_order');
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

        $lesson = Lesson::where('id', $lessonId)
            ->where('course_id', $courseId)
            ->first();

        if (!$lesson) {
            return response_error('Invalid lesson or course ID.', [], 404);
        }

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
        $courses = Course::where('is_published', true)
            ->withSum('lessons', 'duration')
            ->latest()
            ->paginate(10);

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

        if (!auth('sanctum')->check()) {
            return response()->json(['ok' => false, 'message' => 'Please login to view this course.'], 401);
        }

        $user = auth('sanctum')->user();
        $course = Course::withSum('lessons', 'duration')
            ->where('is_published', true)
            ->findOrFail($id);
        if (!$this->courseService->hasAccessToCourse($user, $course->id)) {
            return response()->json([
                'ok' => false,
                'message' => 'You must enroll in this course to view details.'
            ], 403); // 403 Forbidden
        }
        $course->load('lessons');
        $course->loadSum('lessons', 'duration');

        return response_success('Course details fetched.', new CourseResource($course));
    }

    public function showPublic($id)
    {

        $course = Course::withSum('lessons', 'duration')->with(['lessons' => function ($q) {
            $q->select('id', 'course_id', 'title', 'duration', 'is_free_preview', 'section_name', 'lesson_type', 'image_path', 'sort_order')
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc');
        }])
            ->where('is_published', true)
            ->findOrFail($id);
        $course->lessons->transform(function ($lesson) {
            $lesson->is_locked = !$lesson->is_free_preview;
            $lesson->video_url = null;
            $lesson->content = null;

            return $lesson;
        });

        return response_success('Course preview details fetched.', new CourseResource($course));
    }

    /**
     * Enroll in a Course
     */
    public function enroll(Request $request, $courseId)
    {
        $request->validate([
            'points_to_redeem' => 'sometimes|integer|min:0'
        ]);
        $taxRate = (float) Setting::getValue('tax_percentage', 0);
        $user = auth()->user();
        $course = Course::where('is_published', true)->findOrFail($courseId);

        //Check if already enrolled
        if ($this->courseService->hasAccessToCourse($user, $courseId)) {
            return response_error('You are already enrolled in this course.', [], 400);
        }

        Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->where('status', 'pending')
            ->delete();

        //Handle Free Course
        if ($course->price <= 0) {
            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'progress_percent' => 0,
                'completed' => false,
                'status' => 'active',
                'final_amount' => 0,
            ]);
            return response_success('Enrolled successfully in free course.', [], 201);
        }


        // Redemption Logic
        $coursePrice = $course->price;
        $pointsToRedeem = $request->points_to_redeem ?? 0;

        $discountAmount = 0;
        $finalAmount = $coursePrice;

        if ($pointsToRedeem > 0) {
            try {

                $redemptionData = $this->rewardService->calculateRedemptionLogic(
                    $user,
                    $pointsToRedeem,
                    $coursePrice
                );

                $discountAmount = $redemptionData['discount_amount'];
                $pointsToRedeem = $redemptionData['points_used'];
                $finalAmount = $redemptionData['new_total'];
            } catch (\Exception $e) {
                return response_error($e->getMessage(), [], 400);
            }
        }


        // Create Pending Record)
        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_price' => $coursePrice,

            'redeemed_points' => $pointsToRedeem,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,

            'status' => 'pending',
            'progress_percent' => 0,
            'completed' => false
        ]);
        $amountInCents = (int) ($finalAmount * 100);

        $paymentResult = $this->paymentService->initializePayment(
            $user,
            $enrollment,
            $amountInCents,
            [
                'type' => 'course_purchase',
                'enrollment_id' => $enrollment->id
            ]
        );

        if (!$paymentResult['success']) {
            $enrollment->delete();
            return response_error($paymentResult['message'] ?? 'Payment init failed.', [], 500);
        }

        return response_success('Payment initialized.', [
            'enrollment_id' => $enrollment->id,
            'payment_details' => $paymentResult,
            'summary' => [
                'original_price' => $coursePrice,
                'discount' => $discountAmount,
                'points_used' => $pointsToRedeem,
                'final_payable' => $finalAmount
            ]
        ]);
    }

    /**
     * Get Home Screen Data
     */
    public function getHomeData()
    {
        $user = auth()->user();

        // My Subscriptions
        $enrollments = Enrollment::with(['course.lessons' => function ($q) {
            $q->select('id', 'course_id', 'title', 'duration', 'section_name', 'lesson_type', 'image_path', 'sort_order');
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

    public function getCourses(Request $request)
    {
        $request->validate([
            'type' => 'required|in:enrolled,popular,trending',
        ]);

        $user = auth()->user();
        $limit = $request->input('per_page', 10);
        $search = $request->input('search');
        $type = $request->type;

        $data = [];
        $pagination = [];
        switch ($type) {
            case 'enrolled':
                $query = Enrollment::with(['course.lessons' => function ($q) {
                    $q->select('id', 'course_id', 'title', 'duration', 'is_free_preview', 'section_name', 'lesson_type', 'image_path', 'sort_order');
                }])
                    ->where('user_id', $user->id);
                if (!empty($search)) {
                    $query->whereHas('course', function ($q) use ($search) {
                        $q->where('title', 'LIKE', "%{$search}%");
                    });
                }

                $enrollments = $query->latest()->paginate($limit);
                $courses = $enrollments->getCollection()->map(function ($enrollment) {
                    $course = $enrollment->course;
                    if ($course) {
                        $course->progress_percent = $enrollment->progress_percent;
                        $course->is_completed = $enrollment->completed;
                        $course->enrolled_at = $enrollment->created_at;
                    }
                    return $course;
                })->filter();
                $data = CourseResource::collection($courses);
                $pagination = [
                    'total' => $enrollments->total(),
                    'per_page' => $enrollments->perPage(),
                    'current_page' => $enrollments->currentPage(),
                    'last_page' => $enrollments->lastPage(),
                ];
                break;

            case 'popular':
                // --- POPULAR COURSES ---
                $query = Course::where('is_published', true)
                    ->withCount('enrollments')
                    ->orderBy('enrollments_count', 'desc');

                if (!empty($search)) {
                    $query->where('title', 'LIKE', "%{$search}%");
                }

                $popularCourses = $query->paginate($limit);

                $data = CourseResource::collection($popularCourses);
                $pagination = [
                    'total' => $popularCourses->total(),
                    'per_page' => $popularCourses->perPage(),
                    'current_page' => $popularCourses->currentPage(),
                    'last_page' => $popularCourses->lastPage(),
                ];
                break;

            case 'trending':
                // --- TRENDING COURSES ---
                $query = Course::where('is_published', true)
                    ->withCount(['enrollments' => function ($q) {
                        // count only enrollments in last 30 days
                        $q->where('created_at', '>=', now()->subDays(30));
                    }])
                    ->orderBy('enrollments_count', 'desc');

                if (!empty($search)) {
                    $query->where('title', 'LIKE', "%{$search}%");
                }

                $trendingCourses = $query->paginate($limit);

                $data = CourseResource::collection($trendingCourses);
                $pagination = [
                    'total' => $trendingCourses->total(),
                    'per_page' => $trendingCourses->perPage(),
                    'current_page' => $trendingCourses->currentPage(),
                    'last_page' => $trendingCourses->lastPage(),
                ];
                break;
        }
        return response()->json([
            'ok' => true,
            'message' => ucfirst($type) . ' courses retrieved successfully.',
            'data' => $data,
            'pagination' => $pagination,
        ]);
    }
}
