<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\PointHistoryResource;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;

class UserRewardController extends Controller
{
    /**
     * Handle the incoming request.
     */

    public function __invoke(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $user = auth()->user();
        $totalPoints = $user->current_points;
        $redeemRate = (float) Setting::getValue('redeem_points_per_usd', 10);
        $monetaryValue = $totalPoints / ($redeemRate > 0 ? $redeemRate : 1);
        $productPoints = $user->pointHistories()
            ->where('rewardable_type', Order::class)
            ->where('type', 'earn')
            ->sum('points');
        $coursePoints = $user->pointHistories()
            ->where('rewardable_type', Enrollment::class)
            ->where('type', 'earn')
            ->sum('points');

        $history = $user->pointHistories()->latest()->paginate($perPage);

        return response()->json([
            'summary' => [
                'total_points' => $totalPoints,
                'monetary_value' => round($monetaryValue, 2),
                'redeem_rate' => $redeemRate,
            ],
            'stats' => [
                'product_points_earned' => (int) $productPoints,
                'course_points_earned' => (int) $coursePoints,
                // 'next_reward_target' => 200,
            ],
            'history' => PointHistoryResource::collection($history)->response()->getData(true),
        ]);
    }
}
