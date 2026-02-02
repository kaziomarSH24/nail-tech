<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Services\TodoService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Stripe\ApiOperations\All;

class TodoController extends Controller
{

    use AuthorizesRequests;
    protected TodoService $todoService;
    public function __construct(TodoService $todoService)
    {
        $this->todoService = $todoService;
        $this->authorizeResource(Todo::class, 'todo');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        $currentDate = $request->input('filter.date') ?? now()->toDateString();


        $todos = QueryBuilder::for(Todo::class)
            ->where('user_id', $userId)
            ->allowedFilters([
                AllowedFilter::scope('date', 'filterDate'),
                AllowedFilter::exact('is_completed'),
            ])
            ->defaultSort('due_date')
            ->get();



        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $weekTasks = Todo::where('user_id', $userId)
            ->whereBetween('due_date', [$startOfWeek, $endOfWeek])
            ->get()
            ->groupBy(function ($task) {
                return $task->due_date->format('Y-m-d');
            });

        $calendarStatus = [];

        for ($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay()) {
            $dateString = $date->format('Y-m-d');

            $status = 'empty';
            $tasksForDay = $weekTasks[$dateString] ?? collect();

            if ($tasksForDay->count() > 0) {
                $allCompleted = $tasksForDay->every(fn($task) => $task->is_completed);

                if ($allCompleted) {
                    $status = 'completed';
                } else {
                    $status = 'pending';
                }
            }

            $calendarStatus[] = [
                'date' => $dateString,
                'day' => $date->format('D'),
                'day_number' => $date->format('d'),
                'status' => $status,
                'is_selected' => $dateString === $currentDate
            ];
        }

        $totalTasks = Todo::where('user_id', $userId)
            ->whereBetween('due_date', [$startOfWeek, $endOfWeek])->count();

        $completedTasks = Todo::where('user_id', $userId)
            ->whereBetween('due_date', [$startOfWeek, $endOfWeek])
            ->where('is_completed', true)->count();

        $percentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        return response()->json([
            'ok' => true,
            'message' => 'Todos retrieved successfully.',
            'data' => $todos,
            'meta' => [
                'calendar_status' => $calendarStatus,

                'weekly_progress' => [
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'percentage' => $percentage,
                    'label' => "{$completedTasks} of {$totalTasks} tasks completed"
                ]
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'due_date' => 'required|date', //2026-02-02 14:30:00
        ]);
        $data = $request->only(['title', 'due_date', 'description']);
        $data['user_id'] = auth()->id();
        $todo = $this->todoService->create($data);
        return response_success('Todo created successfully.', $todo);
    }

    /**
     * Display the specified resource.
     */
    public function show(Todo $todo)
    {
        $todo = $this->todoService->getById($todo->id);
        if (!$todo) {
            return response_error('Todo not found.', [], 404);
        }
        return response_success('Todo retrieved successfully.', $todo);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Todo $todo)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'due_date' => 'sometimes|required|date',
            'is_completed' => 'sometimes|required|boolean',
        ]);
        $data = $request->only(['title', 'due_date', 'description', 'is_completed']);
        $updatedTodo = $this->todoService->update($todo->id, $data);
        return response_success('Todo updated successfully.', $updatedTodo);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Todo $todo)
    {
        $this->todoService->delete($todo->id);
        return response()->json([
            'ok' => true,
            'message' => 'Todo deleted successfully.',
        ]);
    }

    public function toggleStatus($id)
    {
        $todo = Todo::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('update', $todo);
        $todo->update([
            'is_completed' => !$todo->is_completed
        ]);
        return response()->json(['ok' => true, 'message' => 'Status updated.', 'data' => $todo]);
    }
}
