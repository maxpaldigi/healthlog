<?php

namespace App\Policies;

use App\Models\ClinicianTarget;
use App\Models\User;

class ClinicianTargetPolicy
{
    public function view(User $user, ClinicianTarget $clinicianTarget): bool
    {
        return $user->canAccessProfile($clinicianTarget->profile_id);
    }
}
