<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\WeeklyUserMilestone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

class UserObserver extends BaseObserver
{
    public function created(Model $user): void
    {
        parent::created($user);
        if ($user instanceof User && $user->hasRole('user')) {

            $count = User::role('user')
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();
            if ($count > 0 && $count % 5 == 0) {
                $admins = User::role(['super_admin', 'admin'])->get();

                Notification::send($admins, new WeeklyUserMilestone($count));
            }
        }
    }
}
