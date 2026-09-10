<?php

namespace App\Http\Controllers\Care;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\ProfileMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RevokeCaregiverController extends Controller
{
    public function destroy(Request $request, Profile $profile, ProfileMember $member): RedirectResponse
    {
        $this->authorize('revokeMember', [$profile, $member]);

        $member->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Access removed. They can no longer see this log.']);

        return back();
    }
}
