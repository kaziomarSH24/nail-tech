<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Todo;
use App\Models\UserAppointment;
use App\Models\UserFinanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getHomeData()
    {
        $user = auth()->user();
        $today = Carbon::now()->toDateString();
        $now = Carbon::now();

        // ==========================================
        // Today's Income Calculation
        // ==========================================
        $todaysIncome = UserFinanceRecord::where('user_id', $user->id)
            ->whereDate('record_date', $today)
            ->where('type', 'income')
            ->sum('amount');



        $todayTasksQuery = Todo::where('user_id', $user->id)
            ->whereDate('due_date', $today);

        $totalTasksToday = $todayTasksQuery->count();
        $completedTasksToday = $todayTasksQuery->where('is_completed', true)->count();


        $todayAppointmentsQuery = UserAppointment::where('user_id', $user->id)
            ->whereDate('appointment_at', $today);

        $totalAppointmentsToday = $todayAppointmentsQuery->count();

        $completedAppointmentsToday = $todayAppointmentsQuery
            ->where('status', 'completed')
            ->count();


        // ==========================================
        // Weekly To-Do (Preview)
        // ==========================================
        $todos = Todo::where('user_id', $user->id)
            ->where('is_completed', false)
            ->orderBy('due_date', 'asc')
            ->take(3)
            ->get(['id', 'title', 'due_date', 'is_completed']);


        // ==========================================
        // Upcoming Appointment (Nearest One)
        // ==========================================
        $upcomingAppointment = UserAppointment::where('user_id', $user->id)
            ->where('appointment_at', '>', $now)
            ->orderBy('appointment_at', 'asc')
            ->select('id', 'client_name', 'service_type', 'appointment_at')
            ->first();
        $formattedAppointment = null;
        if ($upcomingAppointment) {
            $formattedAppointment = [
                'id' => $upcomingAppointment->id,
                'title' => $upcomingAppointment->client_name,
                'subtitle' => $upcomingAppointment->service_type,
                'time' => Carbon::parse($upcomingAppointment->appointment_at)->format('h:i A'), // "08:00 AM"
                'date' => Carbon::parse($upcomingAppointment->appointment_at)->format('M d'), // "Oct 22"
            ];
        }



        $lastEnrollment = Enrollment::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('completed', 0)
            ->orderBy('updated_at', 'desc')
            ->with('course:id,title,thumbnail')
            ->first();

        $learningProgress = null;

        if ($lastEnrollment && $lastEnrollment->course) {
            $learningProgress = [
                'course_title' => $lastEnrollment->course->title,
                'progress_percent' => (int) $lastEnrollment->progress_percent,
                'last_accessed' => Carbon::parse($lastEnrollment->updated_at)->diffForHumans(),
            ];
        } else {
            $learningProgress = [
                'course_title' => 'No active course',
                'progress_percent' => 0,
                'last_accessed' => 'N/A'
            ];
        }



        $totalPoints = $user->current_points ?? 0;


        // ==========================================
        // Final Response Construction
        // ==========================================
        return response()->json([
            'ok' => true,
            'message' => 'Dashboard data retrieved successfully.',
            'data' => [
                // Header Info
                'user_info' => [
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                ],


                'todays_summary' => [
                    'income' => [
                        'amount' => (float) $todaysIncome,
                        'label' => "Today's Income",
                        'completed' => '$'
                    ],
                    'tasks' => [
                        'total' => $totalTasksToday,
                        'label' => "Tasks Completed",
                        'completed' => $completedTasksToday,
                        // 'label' => "{$completedTasksToday}/{$totalTasksToday} Tasks Done"
                    ],
                    'appointments' => [
                        'total' => $totalAppointmentsToday,
                        'label' => "Appointments",
                        'completed' => $completedAppointmentsToday,
                        // 'label' => "{$completedAppointmentsToday}/{$totalAppointmentsToday} Appointments Done"
                    ]
                ],

                // Section 2: To-Do
                'weekly_todos' => $todos,

                // Section 3: Appointment
                'upcoming_appointment' => $formattedAppointment,

                // Section 4: Learning
                'continue_learning' => $learningProgress,

                // Section 5: Rewards
                'reward_points' => [
                    'total' => (int) $totalPoints,
                    'label' => "You've Earned " . number_format($totalPoints),
                ]
            ]
        ]);
    }
}
