<?php

namespace App\Policies;

use App\Models\BpReading;
use App\Models\User;

class BpReadingPolicy
{
    public function view(User $user, BpReading $bpReading): bool
    {
        return $user->profiles()->whereKey($bpReading->profile_id)->exists();
    }

    public function update(User $user, BpReading $bpReading): bool
    {
        return $this->view($user, $bpReading);
    }

    public function delete(User $user, BpReading $bpReading): bool
    {
        return $this->view($user, $bpReading);
    }
}
