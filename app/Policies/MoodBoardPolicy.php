<?php

namespace App\Policies;

use App\Models\MoodBoard;
use App\Models\User;

class MoodBoardPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MoodBoard $moodBoard): bool
    {
        return $user->id === $moodBoard->user_id;
    }


    public function update(User $user, MoodBoard $moodBoard): bool
    {
        return $user->id === $moodBoard->user_id;
    }


    public function delete(User $user, MoodBoard $moodBoard): bool
    {
        return $user->id === $moodBoard->user_id;
    }
    
    public function create(User $user): bool
    {
        return true;
    }
}
