<?php

namespace App\Services;

use App\Services\BaseService;
use App\Models\DailyReflection;
use Spatie\QueryBuilder\AllowedFilter;
use App\Filters\GlobalSearchFilter;

class DailyReflectionService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = DailyReflection::class;
    protected bool $cachePerUser = true;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }


    // Define allowed filters
    protected function getAllowedFilters(): array
    {
        return [

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
            'entry_date',
            'created_at',
        ];
    }


    //update or create
    public function updateOrCreate(array $data): DailyReflection
    {
        $reflection = DailyReflection::updateOrCreate(
            [
                'user_id' => $data['user_id'],
                'entry_date' => $data['entry_date']
            ],
            [
                'grateful_for' => $data['grateful_for'],
                'improvement_focus' => $data['improvement_focus'],
                'inspired_by' => $data['inspired_by'],
                'weekly_progress' => $data['weekly_progress'],
            ]
        );

        return $reflection;
    }

    public function calculateStreak($userId)
    {
        $streak = 0;
        $date = now();

        while (true) {
            $hasEntry = DailyReflection::where('user_id', $userId)
                ->whereDate('entry_date', $date->toDateString())
                ->exists();

            if ($hasEntry) {
                $streak++;
                $date->subDay();
            } else {
                if ($streak == 0 && $date->isToday()) {
                    $date->subDay();
                    continue;
                }
                break;
            }
        }
        return $streak;
    }
}
