<?php

namespace App\Policies;

use App\Models\Hba1cResult;
use App\Models\User;

class Hba1cResultPolicy
{
    public function update(User $user, Hba1cResult $hba1cResult): bool
    {
        return $user->canAccessProfile($hba1cResult->profile_id);
    }

    public function delete(User $user, Hba1cResult $hba1cResult): bool
    {
        return $this->update($user, $hba1cResult);
    }
}
