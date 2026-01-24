<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\RewardService;
use Illuminate\Http\Request;

class RedemptionController extends Controller
{
    public function calculateDiscount(Request $request, RewardService $rewardService)
    {
        $request->validate([
            'points_to_redeem' => 'required|integer|min:1',
            'cart_total' => 'required|numeric|min:0'
        ]);

        try {

            $result = $rewardService->calculateRedemptionLogic(
                auth()->user(),
                $request->points_to_redeem,
                $request->cart_total
            );

            return response()->json([
                'success' => true,
                'points_applied' => $result['points_used'], 
                'discount_amount' => $result['discount_amount'],
                'new_cart_total' => $result['new_total'],
                'message' => "Redeemed {$result['points_used']} points for $" . $result['discount_amount'] . " off!"
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
