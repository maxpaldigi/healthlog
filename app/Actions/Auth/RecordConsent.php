<?php

namespace App\Actions\Auth;

use App\Enums\ConsentType;
use App\Models\Consent;
use App\Models\User;

class RecordConsent
{
    public function handle(User $user, ConsentType $type): Consent
    {
        return $user->consents()->create([
            'type' => $type,
            'granted_at' => now(),
            'ip_address' => request()->ip(),
            'policy_version' => Consent::POLICY_VERSION,
        ]);
    }
}
