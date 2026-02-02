<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Financerequest;
use App\Http\Resources\Financeresource;
use App\Models\UserFinanceRecord;
use App\Services\UserFinanceRecordService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FinanceController extends Controller
{
    protected UserFinanceRecordService $financeService;
    use AuthorizesRequests;
    public function __construct(UserFinanceRecordService $financeService)
    {
        $this->financeService = $financeService;
        $this->authorizeResource(UserFinanceRecord::class, 'finance_record');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = auth()->id();

        $summaryQuery = UserFinanceRecord::where('user_id', $userId);

        if (request()->filled('filter.date_between')) {
            $summaryQuery->dateBetween(request()->input('filter.date_between'));
        }

        $summaryStats = $summaryQuery
            ->select('type', DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');
            

        $income = (float) ($summaryStats['income'] ?? 0);
        $expense = (float) ($summaryStats['expense'] ?? 0);

        $profitRaw = $income - $expense;
        $profit = $profitRaw > 0 ? $profitRaw : 0;
        $loss = $profitRaw < 0 ? abs($profitRaw) : 0;

        $financeRecords = QueryBuilder::for(UserFinanceRecord::class)
            ->where('user_id', $userId)
            ->allowedFilters([
                AllowedFilter::scope('date_between'),
                'type',
            ])
            ->allowedSorts(['record_date', 'amount', 'created_at'])
            ->defaultSort('-record_date')
            ->paginate(request('per_page', 10));

        return Financeresource::collection($financeRecords)->additional([
            'ok' => true,
            'message' => 'Finance records retrieved successfully.',
            'summary' => [
                'total_income' => $income,
                'total_expense' => $expense,
                'profit' => $profit,
                'loss' => $loss,
                'balance' => $profitRaw,
            ],
        ]);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Financerequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $financeRecord = $this->financeService->create($data);
        return new Financeresource($financeRecord)->additional([
            'ok' => true,
            'message' => 'Finance record created successfully.',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserFinanceRecord $finance_record)
    {
        $record = $this->financeService->getById($finance_record->id);
        return new Financeresource($record)->additional([
            'ok' => true,
            'message' => 'Finance record retrieved successfully.',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Financerequest $request, UserFinanceRecord $finance_record)
    {
        $data = $request->validated();
        $updatedRecord = $this->financeService->update($finance_record->id, $data);
        return new Financeresource($updatedRecord)->additional([
            'ok' => true,
            'message' => 'Finance record updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserFinanceRecord $finance_record)
    {
        $this->financeService->delete($finance_record->id);
        return response()->json([
            'ok' => true,
            'message' => 'Finance record deleted successfully.',
        ]);
    }

    public function getAnalytics(Request $request)
    {
        $data = $this->financeService->getAnalytics();
        return response_success('Analytics data.', $data);
    }
}
