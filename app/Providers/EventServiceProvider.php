<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\MoodBoard;
use App\Models\MoodBoardImage;
use App\Models\Product;
use App\Models\UserAppointment;
use App\Models\UserFinanceRecord;
use App\Observers\AppointmentObserver;
use App\Observers\CourseObserver;
use App\Observers\FinanceObserver;
use App\Observers\LessonsObserver;
use App\Observers\MoodBoardImageObserver;
use App\Observers\MoodBoardObserver;
use App\Observers\ProductObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{

    protected $listen = [
        // --- for stripe webhook listener ---
        \Laravel\Cashier\Events\WebhookReceived::class => [
            \App\Listeners\StripeEventListener::class,
        ],
        // ------------------------------------

        'Illuminate\Auth\Events\Registered' => [
            'Illuminate\Auth\Listeners\SendEmailVerificationNotification',
        ],
    ];

    // //Observers
    // protected $observers = [
    //     Product::class => [ProductObserver::class],
    //     Course::class => [CourseObserver::class],
    //     Lesson::class => [LessonsObserver::class],
    // ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        parent::boot();

        //observers
        Course::observe(CourseObserver::class);
        Product::observe(ProductObserver::class);
        Lesson::observe(LessonsObserver::class);
        UserAppointment::observe(AppointmentObserver::class);
        UserFinanceRecord::observe(FinanceObserver::class);
        MoodBoard::observe(MoodBoardObserver::class);
        MoodBoardImage::observe(MoodBoardImageObserver::class);
    }
}
