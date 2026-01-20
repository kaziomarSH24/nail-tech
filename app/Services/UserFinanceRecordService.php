<?php

namespace App\Services;

use App\Services\BaseService;
use App\Models\UserFinanceRecord;
use Spatie\QueryBuilder\AllowedFilter;
use App\Filters\GlobalSearchFilter;
use Illuminate\Support\Facades\DB;

class UserFinanceRecordService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = UserFinanceRecord::class;
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
            // 'type',
            'category',
            'client_or_vendor_name',
            AllowedFilter::exact('status'),
            AllowedFilter::exact('type'),
            // Range Filter: ?filter[date_between]=2025-01-01,2025-01-31
            AllowedFilter::scope('date_between', 'dateBetween'),
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
        return ['record_date', 'amount'];
    }


    public function getAnalytics()
    {
        $callback = function () {
            $userId = auth()->id();
            $now = now();

            // 1. Calculate Cards (Current Month)
            $summary = $this->model->where('user_id', $userId)
                ->whereMonth('record_date', $now->month)
                ->whereYear('record_date', $now->year)
                ->select('type', DB::raw('SUM(amount) as total'))
                ->groupBy('type')
                ->pluck('total', 'type');

            $income = (float) ($summary['income'] ?? 0);
            $expense = (float) ($summary['expense'] ?? 0);
            $profitRaw = $income - $expense;

            // 2. Calculate Chart (Last 6 Months with Zero-Fill)
            $sixMonthsAgo = now()->subMonths(5)->startOfMonth();

            $dbData = $this->model->where('user_id', $userId)
                ->where('type', 'income')
                ->where('record_date', '>=', $sixMonthsAgo)
                ->select(DB::raw("DATE_FORMAT(record_date, '%Y-%m') as m_key"), DB::raw("SUM(amount) as total"))
                ->groupBy('m_key')
                ->pluck('total', 'm_key');

            $chartData = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $key = $date->format('Y-m');
                $chartData[] = [
                    'month_year' => $key,
                    'month_name' => $date->format('M'),
                    'total_income' => (float) ($dbData[$key] ?? 0)
                ];
            }

            return [
                'cards' => [
                    'total_income' => $income,
                    'total_expense' => $expense,
                    'profit' => max(0, $profitRaw),
                    'loss' => $profitRaw < 0 ? abs($profitRaw) : 0,
                ],
                'chart_data' => $chartData
            ];
        };

        // Cache this heavy calculation
        return $this->cache('getAnalytics', [], $callback, 3600, true);
    }
}
