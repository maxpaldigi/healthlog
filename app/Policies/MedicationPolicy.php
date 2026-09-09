<?php

namespace App\Policies;

use App\Models\Medication;
use App\Models\User;

class MedicationPolicy
{
    public function update(User $user, Medication $medication): bool
    {
        return $user->profiles()->whereKey($medication->profile_id)->exists();
    }

    public function delete(User $user, Medication $medication): bool
    {
        return $this->update($user, $medication);
    }
}
