<?php

namespace App\Policies;

use App\Models\FoodLog;
use App\Models\User;

class FoodLogPolicy
{
    public function view(User $user, FoodLog $foodLog): bool
    {
        return $user->canAccessProfile($foodLog->profile_id);
    }

    public function delete(User $user, FoodLog $foodLog): bool
    {
        return $this->view($user, $foodLog);
    }
}
