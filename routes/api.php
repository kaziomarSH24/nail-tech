<?php

use App\Http\Controllers\Api\V1\Admin\FaqController;
use App\Http\Controllers\Api\V1\Admin\IncomeReportController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\RewardSettingController;
use App\Http\Controllers\Api\V1\Admin\SettingController;
use App\Http\Controllers\Api\V1\Admin\StockController;
use App\Http\Controllers\Api\V1\Admin\UserManagementController;
use App\Http\Controllers\Api\V1\Admin\WebhookController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordController;
use App\Http\Controllers\Api\V1\Auth\ProfileController;
use App\Http\Controllers\Api\V1\Auth\VerificationController;
use App\Http\Controllers\Api\V1\Chat\ConversationController;
use App\Http\Controllers\Api\V1\Chat\GroupController;
use App\Http\Controllers\Api\V1\Chat\MessageController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\Payment\InvoiceController;
use App\Http\Controllers\Api\V1\Payment\OneTimePaymentController;
use App\Http\Controllers\Api\V1\Payment\PaymentMethodController;
use App\Http\Controllers\Api\V1\Payment\RefundController;
use App\Http\Controllers\Api\V1\Payment\StripePortalController;
use App\Http\Controllers\Api\V1\Payment\SubscriptionController;
use App\Http\Controllers\Api\V1\User\AppointmentControll;
use App\Http\Controllers\Api\V1\User\CourseController as UserCourseController;
use App\Http\Controllers\Api\V1\User\FinanceController;
use App\Http\Controllers\Api\V1\User\MoodBoardController;
use App\Http\Controllers\Api\V1\User\OrderController;
use App\Http\Controllers\Api\V1\User\RedemptionController;
use App\Http\Controllers\Api\V1\User\ShopController;
use App\Http\Controllers\Api\V1\User\TodoController;
use App\Http\Controllers\Api\V1\User\UserRewardController;
use Laravel\Cashier\Http\Controllers\WebhookController as ControllersWebhookController;

Route::post(
    '/v1/stripe/webhook',
    [ControllersWebhookController::class, 'handleWebhook']
)->name('cashier.webhook');

// Shopify Webhook Route
Route::prefix('webhooks/shopify')->group(function () {
    Route::post('/products', [WebhookController::class, 'handleProductWebhook']);
    Route::post('/orders', [WebhookController::class, 'handleOrderUpdate']);
});

// --- Public Routes (Authentication) ---
Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.v1.auth.login');

    Route::post('/verify', [VerificationController::class, 'verify'])->name('api.v1.auth.verify');
    Route::post('/resend-verification', [VerificationController::class, 'resendVerification'])->name('api.v1.auth.resendVerification');

    Route::post('/forgot-password', [PasswordController::class, 'forgotPassword'])->name('api.v1.auth.forgotPassword');
    Route::post('/verify-password-otp', [PasswordController::class, 'verifyResetOtp'])->name('api.v1.auth.verifyResetOtp');
    Route::post('/reset-password-with-token', [PasswordController::class, 'resetPasswordWithToken'])->name('api.v1.auth.resetPasswordWithToken');
});

// Route::post('/upload', [FileController::class, 'handleRequest'])->name('api.v1.file.upload');

// --- Protected Routes (User must be logged in) ---
Route::middleware('auth:sanctum', 'throttle:api')->prefix('v1')->group(function () {

    // Auth related protected routes
    Route::prefix('auth')->name('api.v1.auth.')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/update-password', [PasswordController::class, 'updatePassword'])->name('updatePassword');
    });

    // Profile related protected routes
    Route::prefix('profile')->name('api.v1.profile.')->group(function () {
        Route::get('/me', [ProfileController::class, 'me'])->name('me');
        Route::post('/update', [ProfileController::class, 'updateProfile'])->name('update');
    });

    /**
     ** Chat Module Routes
     */
    Route::prefix('chat')->name('api.v1.chat.')->group(function () {
        // Conversations
        Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::post('/conversations', [ConversationController::class, 'store'])->name('conversations.store');

        // Messages
        Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
        Route::patch('/messages/{message}', [MessageController::class, 'update'])->name('messages.update');
        Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
        Route::post('/messages/read', [MessageController::class, 'markAsRead'])->name('messages.read');

        // Group Management
        Route::post('/groups/{conversation}/members', [GroupController::class, 'addMember'])->name('groups.members.add');
        Route::delete('/groups/{conversation}/members', [GroupController::class, 'removeMember'])->name('groups.members.remove');
        Route::post('/groups/{conversation}/leave', [GroupController::class, 'leaveGroup'])->name('groups.leave');
        Route::post('/groups/{conversation}/promote', [GroupController::class, 'promoteToAdmin'])->name('groups.promote');
        Route::post('/groups/{conversation}/demote', [GroupController::class, 'demoteToMember'])->name('groups.demote');

        // Real-time
        Route::post('/conversations/{conversation}/typing', [MessageController::class, 'typing'])->name('typing');
    });


    //**---Payment Method routes---**//
    Route::prefix('payment')->name('api.v1.payment.')->group(function () {

        // One-time payment routes
        Route::prefix('one-time')->name('one-time.')->group(function () {
            Route::post('/checkout-session', [OneTimePaymentController::class, 'createCheckoutSession'])->name('checkout-session');
            Route::post('/payment-intent', [OneTimePaymentController::class, 'createPaymentIntent'])->name('payment-intent');
        });

        // Subscription routes
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::post('/', [SubscriptionController::class, 'createSubscription'])->name('create');
            Route::get('/', [SubscriptionController::class, 'showSubscription'])->name('show');
            Route::post('/cancel', [SubscriptionController::class, 'cancelSubscription'])->name('cancel');
            Route::post('/resume', [SubscriptionController::class, 'resumeSubscription'])->name('resume');
            Route::post('/swap', [SubscriptionController::class, 'swapPlan'])->name('swap');
        });

        // Refund routes
        Route::prefix('refunds')->name('refunds.')->group(function () {
            Route::post('/', [RefundController::class, 'requestRefund'])->name('request');
        });

        // Invoice routes
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [InvoiceController::class, 'index'])->name('index');
            Route::get('/{invoice}/download', [InvoiceController::class, 'download'])->name('download');
        });

        // Payment method routes
        Route::prefix('payment-methods')->name('payment-methods.')->group(function () {
            Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
            Route::post('/', [PaymentMethodController::class, 'store'])->name('store');
            Route::patch('/{paymentMethod}/set-default', [PaymentMethodController::class, 'setDefault'])->name('set-default');
            Route::delete('/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('destroy');
            Route::delete('/', [PaymentMethodController::class, 'destroyAll'])->name('destroy-all');
            //createSetupIntent
            Route::post('/setup-intent', [PaymentMethodController::class, 'createSetupIntent'])->name('setup-intent');
            //createSetupSession for save card
            Route::post('/setup-session', [PaymentMethodController::class, 'createSetupSession'])->name('setup-session');
        });

        // Stripe billing portal route
        Route::post('/billing-portal', [StripePortalController::class, 'redirectToPortal'])->name('billing-portal');
    });


    //***--- Notification Routes ---***/
    Route::prefix('notifications')->name('api.v1.notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/stats', [NotificationController::class, 'stats'])->name('stats');
        Route::post('/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    });


    Route::middleware(['role:admin|super_admin'])->prefix('admin')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->withoutMiddleware('role:admin|super_admin'); // Get List
        Route::get('/products/{id}', [ProductController::class, 'show'])->withoutMiddleware('role:admin|super_admin'); // Get Details
        Route::get('/product/categories', [ProductController::class, 'getCategories'])->withoutMiddleware('role:admin|super_admin'); // Get Categories
        Route::post('/products/sync', [ProductController::class, 'sync']); // Manual Sync Button

        Route::get('/stocks/overview', [StockController::class, 'index']);
        Route::get('/stocks/requests', [StockController::class, 'restockRequests']);
        Route::put('/stocks/requests/{id}', [StockController::class, 'updateRestockStatus']);

        //** Courses Route */
        Route::apiResource('courses', CourseController::class)->except(['edit', 'create']);
        Route::put('courses/{course}/status', [CourseController::class, 'updatePublishedStatus']);
        //video upload
        Route::post('/courses/upload-video', [CourseController::class, 'uploadCourseVideo'])
            ->withoutMiddleware(['throttle', 'throttle:api']);
        // Add Lesson to Course
        Route::post('/courses/{id}/lessons', [CourseController::class, 'addLesson']);
        Route::put('/courses/{courseId}/lessons/{lessonId}', [CourseController::class, 'updateLesson']);
        Route::delete('/courses/{courseId}/lessons/{lessonId}', [CourseController::class, 'deleteLesson']);

        //user management
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::get('/users/{id}', [UserManagementController::class, 'show']);

        //income report
        Route::get('/income-report', [IncomeReportController::class, 'index']);
        Route::get('/income-report/{id}', [IncomeReportController::class, 'show']);
        Route::get('/income/generate-report', [IncomeReportController::class, 'generateReport']);

        // Reward Settings
        Route::get('/reward-settings', [RewardSettingController::class, 'index']);
        Route::post('/reward-settings', [RewardSettingController::class, 'update']);

        //Settings Routes
        Route::prefix('settings')->name('api.v1.admin.settings.')->group(function () {
            // Terms Routes
            Route::get('/terms', [SettingController::class, 'getTerms']);
            Route::post('/terms', [SettingController::class, 'updateTerms']);

            // Contact Info Routes
            Route::get('/contact-info', [SettingController::class, 'getContactInfo']);
            Route::post('/contact-info', [SettingController::class, 'updateContactInfo']);
            Route::delete('/contact-info', [SettingController::class, 'deleteContactInfo']);
            //tex preference
            Route::get('/tax-shipping', [SettingController::class, 'getTaxAndShipping']);
            Route::post('/tax-shipping', [SettingController::class, 'updateTaxAndShipping']);

            // faq
            Route::apiResource('faqs', FaqController::class);
        });
    });

    //Users Course Routes
    Route::get('/courses', [UserCourseController::class, 'index']);
    Route::get('/courses/{id}', [UserCourseController::class, 'show']);
    Route::get('/courses/{id}/preview', [UserCourseController::class, 'showPublic']);
    Route::post('/courses/{courseId}/enroll', [UserCourseController::class, 'enroll']);
    Route::post('/courses/{courseId}/lessons/{lessonId}/complete', [UserCourseController::class, 'completeLesson']);
    Route::get('/course/enrolled', [UserCourseController::class, 'getEnrolledCourses']);
    // Route::get('/courses/{courseId}/lessons/{lessonId}/access', [UserCourseController::class, 'accessLesson']);
    // --- Home Page Routes ---
    Route::get('/home', [UserCourseController::class, 'getHomeData']);
    // --- See All Routes (With Pagination) ---
    Route::get('/course/popular', [UserCourseController::class, 'getPopularCourses']);
    Route::get('/course/trending', [UserCourseController::class, 'getTrendingCourses']);
    Route::get('/all/courses', [UserCourseController::class, 'getCourses']);
    //**Appointment Routes */
    Route::apiResource('appointments', AppointmentControll::class)->except(['create', 'edit']);
    Route::get('appointment/yearly-status', [AppointmentControll::class, 'getMonthlyAppointments']);
    //**Finance Records Routes (income tracker) */
    Route::apiResource('finance-records', FinanceController::class)->except(['create', 'edit']);
    //analytics data
    Route::get('finance/analytics', [FinanceController::class, 'getAnalytics']);

    //--- Mood Board Routes ---
    Route::apiResource('mood-boards', MoodBoardController::class)->except(['create', 'edit']);
    //get images from album
    Route::get('mood-boards/{mood_board}/images', [MoodBoardController::class, 'getAlbumImages']);
    //upload image to album
    Route::post('mood-boards/images/upload', [MoodBoardController::class, 'uploadImage']);
    //delete image from album
    Route::delete('mood-boards/images/{imageId}', [MoodBoardController::class, 'deleteImage']);
    //get single image details
    Route::get('mood-boards/images/details/{imageId}', [MoodBoardController::class, 'getImageDetails']);


    //product checkout
    Route::post('/shop/checkout', [ShopController::class, 'initiateCheckout'])->name('api.v1.shop.checkout');
    //order history
    Route::get('/shop/orders', [OrderController::class, 'index'])->name('api.v1.shop.orders');
    Route::get('/shop/orders/{order}', [OrderController::class, 'show'])->name('api.v1.shop.orders.details');

    //--- User Reward Routes ---
    Route::get('/user/rewards', UserRewardController::class)->name('api.v1.user.rewards');
    //Redeem points
    Route::post('/user/rewards/redeem', [RedemptionController::class, 'calculateDiscount'])->name('api.v1.user.rewards.redeem');

    //--- Todo Routes ---
    Route::apiResource('todos', TodoController::class)->except(['create', 'edit']);
    Route::put('/todos/{id}/toggle', [TodoController::class, 'toggleStatus']);













    Route::fallback(function () {
        return response_error('The requested API endpoint does not exist.', [], 404);
    });
});
