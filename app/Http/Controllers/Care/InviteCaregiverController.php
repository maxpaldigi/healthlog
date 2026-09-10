<?php

namespace App\Http\Controllers\Care;

use App\Actions\Care\InviteCaregiver;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InviteCaregiverController extends Controller
{
    public function store(Request $request, Profile $profile, InviteCaregiver $inviteCaregiver): RedirectResponse
    {
        $this->authorize('invite', $profile);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $inviteCaregiver->handle($profile, $validated['email'], $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invite sent. They will be visible here after they accept.']);

        return back();
    }
}
