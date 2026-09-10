<?php

namespace App\Actions\Care;

use App\Enums\ProfileRole;
use App\Models\Profile;
use App\Models\ProfileMember;
use App\Models\User;
use App\Notifications\CaregiverInviteNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class InviteCaregiver
{
    public function handle(Profile $profile, string $email, User $inviter): ProfileMember
    {
        $email = mb_strtolower(trim($email));

        if ($email === mb_strtolower($inviter->email) || $email === mb_strtolower($profile->user->email)) {
            throw ValidationException::withMessages([
                'email' => 'You cannot invite the person who owns this log.',
            ]);
        }

        $existing = $profile->members()->where('email', $email)->first();

        if ($existing?->isAccepted()) {
            throw ValidationException::withMessages([
                'email' => 'That person already has access.',
            ]);
        }

        $invitee = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if ($invitee !== null && $invitee->isOwnerOf($profile)) {
            throw ValidationException::withMessages([
                'email' => 'That person already owns this log.',
            ]);
        }

        $member = $existing ?? $profile->members()->make();
        $member->fill([
            'email' => $email,
            'user_id' => $invitee?->id,
            'role' => ProfileRole::Caregiver,
            'invited_at' => now(),
            'accepted_at' => null,
        ]);
        $member->save();

        $url = URL::temporarySignedRoute(
            'invites.accept',
            now()->addDays(7),
            ['member' => $member->id],
        );

        Notification::route('mail', $email)
            ->notify(new CaregiverInviteNotification($url, $profile->name, $inviter->name));

        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            session()->flash('dev_invite_url', $url);
        }

        return $member;
    }
}
