<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserFinanceRecord;

class UserFinanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UserFinanceRecord $financeRecord): bool
    {
        return $user->id === $financeRecord->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, UserFinanceRecord $financeRecord): bool
    {
        return $user->id === $financeRecord->user_id;
    }

    public function delete(User $user, UserFinanceRecord $financeRecord): bool
    {
        return $user->id === $financeRecord->user_id;
    }
}
