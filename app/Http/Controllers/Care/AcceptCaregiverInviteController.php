<?php

namespace App\Http\Controllers\Care;

use App\Actions\Care\AcceptCaregiverInvite;
use App\Http\Controllers\Controller;
use App\Models\ProfileMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AcceptCaregiverInviteController extends Controller
{
    public function __invoke(Request $request, ProfileMember $member, AcceptCaregiverInvite $accept): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()
                ->route('login')
                ->with('status', 'Log in or register with '.$member->email.' to accept this invite.');
        }

        if ($member->isAccepted() && $user->canAccessProfile($member->profile)) {
            $request->session()->put('current_profile_id', $member->profile_id);

            return to_route('home');
        }

        try {
            $accept->handle($member, $user);
        } catch (ValidationException $e) {
            return redirect()->route('home')->withErrors($e->errors());
        }

        $request->session()->put('current_profile_id', $member->profile_id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'You can now help with this log. They can see that you have access.']);

        return to_route('home');
    }
}
