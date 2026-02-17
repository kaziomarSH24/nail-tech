<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    protected UserService $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }



    public function index(Request $request)
    {
        $qc = fn($q) => $q->select('id', 'name', 'email', 'avatar', 'phone', 'dob', 'status','is_suspended', 'created_at');
        $user = $this->userService->getAll($qc);
        if ($user->isEmpty()) {
            return response_error('No users found.', [], 404);
        }
        return UserResource::collection($user);
    }

    public function show($id)
    {
        $with = [
            "appointments" => function ($query) {
                $query->select('id', 'user_id', 'client_name', 'status', 'created_at')
                    ->latest()->limit(5);
            },
            "enrollments.course" => function ($query) {
                $query->select('id', 'title', 'slug', 'thumbnail', 'created_at');
            },
        ];

        $user = $this->userService->getById($id, $with);
        if (!$user) {
            return response_error('User not found.', [], 404);
        }
        return new UserResource($user);
    }

    public function toggleSuspend($id)
    {
        $user = User::findOrFail($id);
        if (auth()->id() === $user->id) {
            return response_error('You cannot suspend your own account.', [], 403);
        }
        $user->is_suspended = !$user->is_suspended;
        $user->save();

        $status = $user->is_suspended ? 'Suspended' : 'Activated';

        return response_success("User has been $status successfully.");
    }
}
