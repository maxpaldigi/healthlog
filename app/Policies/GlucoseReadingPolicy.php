<?php

namespace App\Policies;

use App\Models\GlucoseReading;
use App\Models\User;

class GlucoseReadingPolicy
{
    public function view(User $user, GlucoseReading $glucoseReading): bool
    {
        return $user->profiles()->whereKey($glucoseReading->profile_id)->exists();
    }

    public function update(User $user, GlucoseReading $glucoseReading): bool
    {
        return $this->view($user, $glucoseReading);
    }

    public function delete(User $user, GlucoseReading $glucoseReading): bool
    {
        return $this->view($user, $glucoseReading);
    }
}
