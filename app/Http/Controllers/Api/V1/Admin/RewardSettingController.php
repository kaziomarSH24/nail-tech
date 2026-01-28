<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointHistory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

class RewardSettingController extends Controller
{
    public function index()
    {

        $settings = [
            'redeem_points_per_usd' => (float) Setting::getValue('redeem_points_per_usd', 20),
            'earn_points_per_usd'   => (float) Setting::getValue('earn_points_per_usd', 1),
        ];


        $users = User::query()

            ->withSum(['pointHistories as total_points_earned' => function ($query) {
                $query->where('type', 'earn');
            }], 'points')

            ->withSum(['pointHistories as total_points_redeemed' => function ($query) {
                $query->where('type', 'redeem');
            }], 'points')
            ->addSelect([
                'last_redeemed_at' => PointHistory::select('created_at')
                    ->whereColumn('user_id', 'users.id')
                    ->where('type', 'redeem')
                    ->latest()
                    ->limit(1)
            ])

            ->latest()
            ->paginate(10);

        $mappedUsers = $users->getCollection()->map(function ($user) {
            return [
                'id'               => $user->id,
                'user_name'        => $user->first_name . ' ' . $user->last_name,
                'email'            => $user->email,

                'available_points' => $user->current_points,

                'total_points'     => (int) $user->total_points_earned,

                'redeemed_points'  => abs((int) $user->total_points_redeemed),

                'last_redeemed'    => $user->last_redeemed_at
                    ? \Carbon\Carbon::parse($user->last_redeemed_at)->format('M d, Y')
                    : 'N/A',
            ];
        });


        return response()->json([
            'settings' => $settings,
            'report'   => [
                'data' => $mappedUsers,
                'pagination' => [
                    'total'        => $users->total(),
                    'current_page' => $users->currentPage(),
                    'per_page'     => $users->perPage(),
                    'links'        => $users->linkCollection(),
                ]
            ]
        ]);
    }


    public function update(Request $request)
    {
        $request->validate([
            'redeem_points_per_usd' => 'required|numeric|min:1',
            'earn_points_per_usd' => 'required|numeric|min:0',
        ]);


        Setting::setValue('redeem_points_per_usd', $request->redeem_points_per_usd);
        Setting::setValue('earn_points_per_usd', $request->earn_points_per_usd);

        return response()->json([
            'ok' => true,
            'message' => 'Reward settings updated successfully.',
            'data' => [
                'redeem_points_per_usd' => $request->redeem_points_per_usd,
                'earn_points_per_usd' => $request->earn_points_per_usd,
            ]
        ]);
    }
}
