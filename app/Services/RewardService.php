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

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }


    // Define allowed filters
    protected function getAllowedFilters(): array
    {
        return [
            AllowedFilter::custom('search', new GlobalSearchFilter, 'name', 'email'),
            'name',
            'email',
            AllowedFilter::exact('status'),
        ];
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
            'id',
            'name',
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
            $rate = (float) \App\Models\Setting::getValue('reward_points_per_usd', 100);;
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
}
