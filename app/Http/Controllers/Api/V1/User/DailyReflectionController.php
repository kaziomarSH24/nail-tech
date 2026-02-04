<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyReflectionRequest;
use App\Http\Resources\DailyReflectionResource;
use App\Models\DailyReflection;
use App\Services\DailyReflectionService;
use Illuminate\Http\Request;

class DailyReflectionController extends Controller
{
    protected DailyReflectionService $dailyReflectionService;
    public function __construct(DailyReflectionService $dailyReflectionService)
    {
        $this->dailyReflectionService = $dailyReflectionService;
        $this->authorizeResource(DailyReflection::class, 'daily_reflection');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $qc = function ($query) {
            $query->where('user_id', auth()->id());
        };
        $reflections = $this->dailyReflectionService->getAll($qc);
        if($reflections->isEmpty()){
            return response_error('No daily reflections found.', [], 404);
        }
        return DailyReflectionResource::collection($reflections)->additional([
            'ok' => true,
            'message' => 'Daily reflections retrieved successfully.',
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(DailyReflectionRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $data['entry_date'] = now()->toDateString();

        $reflection = $this->dailyReflectionService->updateOrCreate($data);

        return response_success('Daily reflection created successfully.',$reflection);
    }

    /**
     * Display the specified resource.
     */
    public function show(DailyReflection $daily_reflection)
    {
        $data = $this->dailyReflectionService->getById($daily_reflection->id);

        return DailyReflectionResource::make($data)->additional([
            'ok' => true,
            'message' => 'Daily reflection retrieved successfully.',
        ]);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DailyReflection $daily_reflection)
    {
        $this->dailyReflectionService->delete($daily_reflection->id);
        return response_success('Daily reflection deleted successfully.');
    }

    public function getMonthlyStatus(Request $request)
    {

        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $userId = auth()->id();

        $entryDays = DailyReflection::where('user_id', $userId)
            ->whereYear('entry_date', $request->year)
            ->whereMonth('entry_date', $request->month)
            ->get(['entry_date'])
            ->map(function ($entry) {

                return \Carbon\Carbon::parse($entry->entry_date)->day;
            })
            ->unique()
            ->sort()
            ->values()
            ->all();
        // $currentStreak = $this->dailyReflectionService->calculateStreak($userId);

        return response()->json([
            'ok' => true,
            'message' => "Reflection status for {$request->month}-{$request->year} retrieved.",
            'data' => $entryDays,
            // 'meta' => [
            //     'current_streak' => $currentStreak
            // ]
        ]);
    }

    public function getCurrentStreak()
    {
        $userId = auth()->id();
        $currentStreak = $this->dailyReflectionService->calculateStreak($userId);

        return response_success('Current reflection streak retrieved successfully.', $currentStreak);
    }
}
