<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCourseEnrollment extends Notification
{
    use Queueable;
    protected $enrollment;
    /**
     * Create a new notification instance.
     */
    public function __construct($enrollment)
    {
        $this->enrollment = $enrollment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $user = $this->enrollment->user;
        $course = $this->enrollment->course;

        return [
            'title'   => 'New Course Sold!',
            'message' => "{$user->name} enrolled in \"{$course->title}\"",
            'icon'    => 'book-open', 
            'type'    => 'course_enrollment',   
            'action'  => [
                'link'        => '/admin/enrollments/' . $this->enrollment->id,
                'is_external' => false
            ],
            'meta'    => [
                'amount'        => $this->enrollment->final_amount,
                'enrollment_id' => $this->enrollment->id
            ]
        ];
    }
}
