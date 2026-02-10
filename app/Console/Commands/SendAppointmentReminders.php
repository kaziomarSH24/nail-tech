<?php

namespace App\Console\Commands;

use App\Models\UserAppointment;
use App\Notifications\AppointmentReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointment:send-reminders';
    protected $description = 'Send reminders for upcoming appointments based on user settings';

    public function handle()
    {

        $expiredCount = \App\Models\UserAppointment::where('appointment_at', '<', now())
            ->where('status', 'upcoming')
            ->update(['status' => 'completed']);

        if ($expiredCount > 0) {
            $this->info(" Auto-completed {$expiredCount} past appointments.");
        }


        $startTime = now();
        $endTime = now()->addHours(25);

        $appointments = \App\Models\UserAppointment::with('user')
            ->whereBetween('appointment_at', [$startTime, $endTime])
            ->where('status', 'upcoming')
            ->where('is_reminder_on', true)
            ->get();

        foreach ($appointments as $appointment) {
            $user = $appointment->user;
            if (!$user) continue;
            $minutesRemaining = now()->diffInMinutes($appointment->appointment_at, false);
            $triggerTime = $appointment->reminder_minutes_before ?? 60;

            if ($minutesRemaining >= 1430 && $minutesRemaining <= 1450) {

                $cacheKey = "appt_24h_{$appointment->id}";
                if (($user->notification_settings['appointment_reminder'] ?? true) && !cache()->has($cacheKey)) {

                    $user->notify(new \App\Notifications\AppointmentReminder($appointment, 'tomorrow'));
                    cache()->put($cacheKey, true, 60 * 20);
                    $this->info("24h Reminder sent to User ID: {$user->id}");
                }
            }

            elseif (
                !$appointment->is_reminder_sent &&
                (
                    ($minutesRemaining >= $triggerTime - 2 && $minutesRemaining <= $triggerTime + 2) ||
                    ($minutesRemaining >= 28 && $minutesRemaining <= 32)
                )
            ) {
                if ($user->notification_settings['appointment_reminder'] ?? true) {

                    $user->notify(new \App\Notifications\AppointmentReminder($appointment, 'soon'));

                    $appointment->update(['is_reminder_sent' => true]);

                    $this->info("Final Reminder sent to User ID: {$user->id}");
                }
            }
        }
    }
}


