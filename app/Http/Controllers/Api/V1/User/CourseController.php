<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\CourseService;
use App\Services\PaymentService;
use Illuminate\Http\Request;

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
     * Enroll in a Course (Updated Logic)
     */
    // public function enroll(Request $request, $courseId)
    // {
    //     $user = auth()->user();
    //     $course = Course::findOrFail($courseId);

    //     // 1. Check if already enrolled
    //     if ($this->courseService->hasAccessToCourse($user, $courseId)) {
    //         return response_error('You are already enrolled in this course.', [], 400);
    //     }

    //     // Free Course Logic (Direct Enroll)
    //     if ($course->price <= 0) {
    //         Enrollment::create([
    //             'user_id' => $user->id,
    //             'course_id' => $course->id,
    //             'completed' => false,
    //             'progress_percent' => 0
    //         ]);

    //         return response_success('Enrolled successfully in free course.', [], 201);
    //     }

    //     $amountInCents = (int) ($course->price * 100);

    //     $paymentResult = $this->paymentService->createCheckoutSession(
    //         $user,
    //         $course,
    //         $amountInCents,
    //         "Enrollment: " . $course->title
    //     );

    //     if (!$paymentResult['success']) {
    //         return response_error('Payment initiation failed. Please try again.');
    //     }

    //     return response_success('Payment session created.', [
    //         'payment_required' => true,
    //         'redirect_url' => $paymentResult['redirect_url']
    //     ]);
    // }

    public function enroll(Request $request, $courseId)
    {
        $user = auth()->user();
        $course = Course::where('is_published', true)->findOrFail($courseId);

        //Check if the user is already enrolled
        if ($this->courseService->hasAccessToCourse($user, $courseId)) {
            return response_error('You are already enrolled in this course.', [], 400);
        }

        //Handle Free Course Enrollment
        if ($course->price <= 0) {
            Enrollment::firstOrCreate(
                ['user_id' => $user->id, 'course_id' => $course->id],
                ['progress_percent' => 0, 'completed' => false]
            );

            return response_success('Enrolled successfully in free course.', [], 201);
        }

        // Handle Paid Course
        $request->validate([
            'payment_method_id' => 'required|string'
        ]);

        $amountInCents = (int) ($course->price * 100);

        $paymentResult = $this->paymentService->createPaymentIntent(
            $user,
            $course,
            $amountInCents,
            $request->payment_method_id
        );

        if (!$paymentResult['success']) {
            return response_error($paymentResult['message'] ?? 'Payment failed.', [], 400);
        }

        //Handle 3D Secure (SCA) - If bank requires OTP
        if (!empty($paymentResult['requires_action'])) {
            return response_success('Payment requires authentication.', [
                'requires_action' => true,
                'client_secret' => $paymentResult['client_secret']
            ]);
        }

        //
        // If payment is successful, enroll the user
        $paymentStatus = $paymentResult['payment']->status ?? null;

        if ($paymentStatus === 'succeeded') {
            Enrollment::firstOrCreate(
                ['user_id' => $user->id, 'course_id' => $course->id],
                ['progress_percent' => 0, 'completed' => false]
            );
        }

        return response_success('Payment successful. You are now enrolled.', []);
    }

    /**
     * Get Home Screen Data (My Courses, Popular, Trending)
     */
    public function getHomeData()
    {
        $user = auth()->user();

        // My Subscriptions
        $myCourses = Enrollment::with(['course.lessons' => function ($q) {
            $q->select('id', 'course_id', 'title', 'duration_minutes');
        }])
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->pluck('course');

        // Popular Courses
        $popularCourses = $this->courseService->getPopularCourses(5);

        // Trending Courses
        $trendingCourses = $this->courseService->getTrendingCourses(5);

        return response_success('Home data fetched successfully.', [
            'my_subscriptions' => CourseResource::collection($myCourses),
            'popular_courses'  => CourseResource::collection($popularCourses),
            'trending_courses' => CourseResource::collection($trendingCourses),

            // Banners Data
            // 'banners' => [
            //     ['id' => 1, 'image' => 'banner1.jpg', 'link' => '/course/1'],
            //     ['id' => 2, 'image' => 'banner2.jpg', 'link' => '/products'],
            // ]
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
