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
    public function index(Request $request)
    {
        $qc = function ($query) use ($request) {
            $query->where('user_id', auth()->id());
            if ($request->filled('search')) {
                $query->where('client_or_vendor_name', 'like', '%' . $request->search . '%');
            }
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('record_date', [$request->start_date, $request->end_date]);
            } else {
                $query->whereMonth('record_date', now()->month)
                    ->whereYear('record_date', now()->year);
            }
        };

        $financeRecords = $this->financeService->getAll($qc);
        if ($financeRecords->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'No finance records found.',
            ], 404);
        }
        return Financeresource::collection($financeRecords)->additional([
            'ok' => true,
            'message' => 'Finance records retrieved successfully.',
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
        $userId = auth()->id();

        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();

        $summary = UserFinanceRecord::where('user_id', $userId)
            ->whereBetween('record_date', [$currentMonthStart, $currentMonthEnd])
            ->select('type', DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $income = (float) ($summary['income'] ?? 0);
        $expense = (float) ($summary['expense'] ?? 0);

        $profitRaw = $income - $expense;
        $profit = $profitRaw > 0 ? $profitRaw : 0;
        $loss = $profitRaw < 0 ? abs($profitRaw) : 0;

        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();

        $dbData = UserFinanceRecord::where('user_id', $userId)
            ->where('type', 'income')
            ->where('record_date', '>=', $sixMonthsAgo)
            ->select(
                DB::raw("DATE_FORMAT(record_date, '%Y-%m') as month_key"),
                DB::raw("SUM(amount) as total_amount")
            )
            ->groupBy('month_key')
            ->pluck('total_amount', 'month_key');

        $chartData = collect();

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $monthName = $date->format('M');

            $amount = isset($dbData[$monthKey]) ? (float) $dbData[$monthKey] : 0;

            $chartData->push([
                'month_year' => $monthKey,
                'month_name' => $monthName,
                'total_income' => $amount,
            ]);
        }

        return response_success('Analytics fetched.', [
            'cards' => [
                'total_income' => $income,
                'total_expense' => $expense,
                'profit' => $profit,
                'loss' => $loss,
            ],
            'chart_data' => $chartData,
        ]);
    }
}
