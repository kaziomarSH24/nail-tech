<?php

namespace App\Policies;

use App\Models\Todo;
use App\Models\User;

class TodoPolicy
{

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Todo $todo): bool
    {
        return $user->id === $todo->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Todo $todo): bool
    {
        return $user->id === $todo->user_id;
    }

    public function delete(User $user, Todo $todo): bool
    {
        return $user->id === $todo->user_id;
    }
    
}
