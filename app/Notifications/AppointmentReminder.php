<?php

namespace App\Notifications;

use App\Notifications\Channels\FirebaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminder extends Notification implements ShouldQueue
{
    use Queueable;
    protected $appointment;
    protected $type;

    /**
     * Create a new notification instance.
     */
    public function __construct($appointment, $type)
    {
        $this->appointment = $appointment;
        $this->type = $type;
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
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //         ->line('The introduction to the notification.')
    //         ->action('Notification Action', url('/'))
    //         ->line('Thank you for using our application!');
    // }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $title = $this->type == 'tomorrow'
            ? 'Appointment Tomorrow!'
            : 'Appointment Starting Soon!';

        $message = $this->type == 'tomorrow'
            ? "Reminder: You have an appointment tomorrow at {$this->appointment->appointment_at->format('h:i A')}."
            : "Hurry up! Your appointment starts in few minutes.";

        return [
            'title'   => $title,
            'message' => $message,
            'icon'    => 'clock',
            'type'    => 'warning',
            'action'  => [
                'link' => '/appointments/' . $this->appointment->id,
                'is_external' => false
            ]
        ];
    }

    public function toFirebase($notifiable)
    {
        $tokens = $notifiable->fcmTokens->pluck('token')->toArray();
        if (empty($tokens)) {
            return [];
        }

        $time = $this->appointment->appointment_at->format('h:i A');
        if ($this->type === 'tomorrow') {
            $title = "Appointment Tomorrow!";
            $body = "You have an appointment scheduled for tomorrow at {$time}. Tap to check details.";
        } else {
            $title = "Appointment Starting Soon!";
            $body = "Hurry up! Your appointment starts at {$time}. Please be ready.";
        }

        return [
            'tokens' => $tokens,

            'title' => $title,
            'body'  => $body,
            'data' => [
                'id' => (string) $this->appointment->id,
                'type' => 'appointment_detail',
            ]
        ];
    }
}
