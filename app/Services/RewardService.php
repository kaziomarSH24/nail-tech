<?php

namespace App\Services;

use App\Services\BaseService;
use Spatie\QueryBuilder\AllowedFilter;
use App\Filters\GlobalSearchFilter;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\PointHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RewardService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = PointHistory::class;
    protected bool $cachePerUser = true;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }


    // Define allowed filters
    protected function getAllowedFilters(): array
    {
        return [];
    }

    // Define allowed includes relationships
    protected function getAllowedIncludes(): array
    {
        return [
            //
        ];
    }

    // Define allowed sorts
    protected function getAllowedSorts(): array
    {
        return [
            'created_at',
        ];
    }

    public function distributePoints($model)
    {
        DB::beginTransaction();
        try {
            $points = 0;
            $description = '';
            $user = $model->user;
            $rate = (float) \App\Models\Setting::getValue('earn_points_per_usd', 100);;
            if ($model instanceof Order) {
                $amount = $model->total_amount ?? 0;
                $points = (int) ceil($amount * $rate);

                $description = "Earned from Order #{$model->id}";
            } elseif ($model instanceof Enrollment) {
                $coursePoint = $model->course->reward_points ?? 0;
                Log::info("Course Point: {$coursePoint}");
                if ($coursePoint > 0) {
                    $points = $coursePoint;
                } else {
                    $coursePrice = $model->course->price ?? 0;

                    $points = (int) ceil($coursePrice * $rate);
                }

                $description = "Earned from Course Enrollment #{$model->id}";
            }
            if ($points > 0) {
                $user->increment('current_points', $points);
                $history = new PointHistory();
                $history->user_id = $user->id;
                $history->points = $points;
                $history->type = 'earn';
                $history->description = $description;

                $model->pointHistory()->save($history);

                Log::info("Reward: User {$user->id} earned {$points} points from " . class_basename($model));
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Reward Service Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Redeem points (Deduct from user balance)
     */
    public function redeemPoints($order)
    {
        $pointsToRedeem = $order->redeemed_points;

        if ($pointsToRedeem > 0) {
            $user = $order->user;

            if ($user && $user->current_points >= $pointsToRedeem) {
                $user->decrement('current_points', $pointsToRedeem);
                $history = new PointHistory();
                $history->user_id = $user->id;
                $history->points = - ($pointsToRedeem);
                $history->type = 'redeem';
                $history->description = "Redeemed for Order #{$order->id}";
                $order->pointHistory()->save($history);

                \Log::info("Redemption Success: User {$user->id} used {$pointsToRedeem} points.");
                return true;
            } else {
                \Log::error("Redemption Failed: Insufficient balance for User " . ($user->id ?? 'Unknown'));
                return false;
            }
        }

        return false;
    }

    //calculate redemption logic
    public function calculateRedemptionLogic($user, $pointsToRedeem, $cartTotal)
    {

        if ($user->current_points < $pointsToRedeem) {
            throw new \Exception("Insufficient points balance.");
        }

        $rate = (float) \App\Models\Setting::getValue('redeem_points_per_usd', 10);
        $rate = ($rate > 0) ? $rate : 10;
        $discountAmount = $pointsToRedeem / $rate;

        //if discount amount is greater than cart total then adjust the points used
        $actualPointsUsed = $pointsToRedeem;

        if ($discountAmount > $cartTotal) {
            $discountAmount = $cartTotal;

            // Re-calculate points needed
            $actualPointsUsed = (int) ceil($discountAmount * $rate);
        }

        return [
            'points_used' => $actualPointsUsed,
            'discount_amount' => round($discountAmount, 2),
            'new_total' => round(max(0, $cartTotal - $discountAmount), 2)
        ];
    }
}
