<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserInventory;

class UserInventoryPolicy
{

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, UserInventory $inventory)
    {
        return $user->id === $inventory->user_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, UserInventory $inventory)
    {
        return $user->id === $inventory->user_id;
    }

    public function delete(User $user, UserInventory $inventory)
    {
        return $user->id === $inventory->user_id;
    }
}
