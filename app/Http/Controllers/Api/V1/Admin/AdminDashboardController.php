<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use App\Models\Course;
use App\Models\Order; // বা FinanceRecord
use Spatie\Activitylog\Models\Activity;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $filter = $request->input('filter', 'weekly');

        return response()->json([
            'ok' => true,
            'message' => 'Admin dashboard data retrieved.',
            'data' => [
                'stats' => $this->getStats(),
                'notifications' => $this->getNotifications(),
                'recent_activity' => $this->getRecentActivity(),
            ]
        ]);
    }

    // ==========================================
    // 1. Summary Cards (Stats + Percentage)
    // ==========================================
    private function getStats()
    {
        // --- Total Users ---
        $totalUsers = User::count();
        $lastMonthUsers = User::where('created_at', '<', now()->subMonth())->count();
        $userGrowth = $this->calculateGrowth($totalUsers, $lastMonthUsers);

        // --- Total Products ---
        $totalProducts = Product::count();
        $lastMonthProducts = Product::where('created_at', '<', now()->subMonth())->count();
        $productGrowth = $this->calculateGrowth($totalProducts, $lastMonthProducts);

        // --- Total Courses ---
        $totalCourses = Course::count();
        $yesterdayCourses = Course::where('created_at', '<', now()->subDay())->count();
        $courseGrowth = $this->calculateGrowth($totalCourses, $yesterdayCourses);

        // --- Total Income ---
        $totalIncome = Order::sum('final_amount');
        $yesterdayIncomeTotal = Order::where('created_at', '<', now()->subDay())->sum('final_amount');
        $incomeGrowth = $this->calculateGrowth($totalIncome, $yesterdayIncomeTotal);

        return [
            [
                'label' => 'Total Users',
                'value' => number_format($totalUsers),
                'growth' => $userGrowth . '%',
                'growth_label' => 'from last month',
                'is_positive' => $userGrowth >= 0
            ],
            [
                'label' => 'Total Products',
                'value' => $totalProducts,
                'growth' => $productGrowth . '%',
                'growth_label' => 'from last month',
                'is_positive' => $productGrowth >= 0
            ],
            [
                'label' => 'Total Courses',
                'value' => $totalCourses,
                'growth' => $courseGrowth . '%',
                'growth_label' => 'from yesterday',
                'is_positive' => $courseGrowth >= 0
            ],
            [
                'label' => 'Total Income',
                'value' => '$' . number_format($totalIncome),
                'growth' => $incomeGrowth . '%',
                'growth_label' => 'from yesterday',
                'is_positive' => $incomeGrowth >= 0
            ],
        ];
    }

    public function getIncomeChart(Request $request)
    {
        $filter = $request->input('filter', 'weekly'); 

        return response()->json([
            'ok' => true,
            'data' => $this->getIncomeChartData($filter) 
        ]);
    }

    // =================================================================
    // 2.2 NEW USERS CHART API 👥
    // =================================================================
    public function getNewUsersChart(Request $request)
    {
        $filter = $request->input('filter', 'weekly'); 

        return response()->json([
            'ok' => true,
            'data' => $this->getUserChartData($filter) 
        ]);
    }

    // =================================================================
    // HELPER METHODS (Logic same as before)
    // =================================================================

    private function getIncomeChartData($filter)
    {
        $labels = [];
        $data = [];

        if ($filter === 'weekly') {
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $labels[] = $date->format('D');

                $amount = Order::whereDate('created_at', $date)
                    ->where('status', 'paid')
                    ->sum('final_amount');

                $data[] = (float) $amount;
            }
        } else {
            for ($i = 3; $i >= 0; $i--) {
                $startOfWeek = now()->subWeeks($i)->startOfWeek();
                $endOfWeek = now()->subWeeks($i)->endOfWeek();

                $labels[] = "Week " . (4 - $i);

                $amount = Order::whereBetween('created_at', [$startOfWeek, $endOfWeek])
                    ->where('status', 'paid')
                    ->sum('final_amount');

                $data[] = (float) $amount;
            }
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function getUserChartData($filter)
    {
        $labels = [];
        $data = [];

        if ($filter === 'weekly') {
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $labels[] = $date->format('D');
                $data[] = User::whereDate('created_at', $date)->count();
            }
        } else {
            for ($i = 3; $i >= 0; $i--) {
                $startOfWeek = now()->subWeeks($i)->startOfWeek();
                $endOfWeek = now()->subWeeks($i)->endOfWeek();
                $labels[] = "Week " . (4 - $i);
                $data[] = User::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
            }
        }

        return ['labels' => $labels, 'data' => $data];
    }

    // ==========================================
    // 3. Notifications
    // ==========================================
    private function getNotifications()
    {
        $notifications = auth()->user()->notifications()
            ->latest()
            ->take(10)
            ->get();
        return $notifications->map(function ($notification) {
            $data = $notification->data;

            return [
                'id'      => $notification->id,
                'title'   => $data['title'] ?? 'New Notification',
                'message' => $data['message'] ?? ($data['subtitle'] ?? ''),
                'icon'    => $data['icon'] ?? 'bell',
                'type'    => $data['type'] ?? 'primary',
                'action'  => $data['action'] ?? [
                    'link' => $data['link'] ?? '#',
                    'is_external' => $data['is_external'] ?? false
                ],

                'time'    => $notification->created_at->diffForHumans(),
                'is_read' => !is_null($notification->read_at)
            ];
        });
    }

    // ==========================================
    // 4. Recent Activity (Log / Orders)
    // ==========================================
    private function getRecentActivity()
    {


        return Activity::with('causer')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($activity) {

                $title = $activity->description;

                $userName = $activity->causer->name ?? 'System';
                $subtitle = "";

                $props = $activity->properties;

                if ($title == 'New Order Placed') {
                    $subtitle = "Order #{$props['order_number']} by {$userName}";
                } elseif ($title == 'Course Enrollment') {
                    $subtitle = "{$userName} enrolled in {$props['course_name']}";
                } elseif ($title == 'Product Restocked') {
                    $subtitle = "{$props['quantity_added']} items added by {$userName}";
                } else {
                    $subtitle = "Action by {$userName}";
                }

                return [
                    'id' => $activity->id,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'icon' => $props['icon'] ?? 'activity',
                    'type' => $props['type'] ?? 'info',
                    'time' => $activity->created_at->diffForHumans()
                ];
            });
    }


    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) return 100;
        return round((($current - $previous) / $previous) * 100, 1);
    }
}
