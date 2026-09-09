<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Weight;

class WeightPolicy
{
    public function update(User $user, Weight $weight): bool
    {
        return $user->profiles()->whereKey($weight->profile_id)->exists();
    }

    public function delete(User $user, Weight $weight): bool
    {
        return $this->update($user, $weight);
    }
}
