<?php

namespace App\Policies;

use App\Models\Medication;
use App\Models\User;

class MedicationPolicy
{
    public function update(User $user, Medication $medication): bool
    {
        return $user->canAccessProfile($medication->profile_id);
    }

    public function delete(User $user, Medication $medication): bool
    {
        return $this->update($user, $medication);
    }
}
