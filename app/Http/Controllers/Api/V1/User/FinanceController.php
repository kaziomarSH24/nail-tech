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
    public function index()
    {
        $financeRecords = $this->financeService->getAll(function ($query) {
            $query->where('user_id', auth()->id());
        });
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
       $data = $this->financeService->getAnalytics();
       return response_success('Analytics data.', $data);
    }
}
