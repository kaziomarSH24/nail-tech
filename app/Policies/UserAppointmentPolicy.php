<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserAppointment;

class UserAppointmentPolicy
{


    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view a specific appointment.
     */
    public function view(User $user, UserAppointment $appointment): bool
    {
        return $user->id === $appointment->user_id;
    }

    /**
     * Determine whether the user can create appointments.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the appointment.
     */
    public function update(User $user, UserAppointment $appointment): bool
    {
        return $user->id === $appointment->user_id;
    }

    /**
     * Determine whether the user can delete the appointment.
     */
    public function delete(User $user, UserAppointment $appointment): bool
    {
        return $user->id === $appointment->user_id;
    }
}
