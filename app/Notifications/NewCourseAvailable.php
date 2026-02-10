<?php

namespace App\Notifications;

use App\Notifications\Channels\FirebaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCourseAvailable extends Notification implements ShouldQueue
{
    use Queueable;
    protected $course;
    /**
     * Create a new notification instance.
     */
    public function __construct($course)
    {
        $this->course = $course;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', FirebaseChannel::class];
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'New Course Alert!',
            'message' => "Check out our new course: '{$this->course->title}'",
            'icon'    => 'book-open',
            'type'    => 'info',
            'action'  => [
                'link' => '/courses/' . $this->course->id,
                'is_external' => false
            ]
        ];
    }

    //push notification

    public function toFirebase($notifiable)
    {
        $tokens = $notifiable->fcmTokens->pluck('token')->toArray();

        if (empty($tokens)) {
            return [];
        }


        $imageUrl = $this->course->thumbnail
            ? asset('storage/' . $this->course->thumbnail)
            : null;

        return [
            'tokens' => $tokens,
            'title' => "New Course Alert! ",
            'body' => "Master \"{$this->course->title}\" today. Tap to start learning!",
            'image' => $imageUrl,
            'data' => [
                'id' => (string) $this->course->id,
                'type' => 'course_detail',
                'image' => $imageUrl
            ]
        ];
    }
}
