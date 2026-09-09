<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CurrentProfileController extends Controller
{
    public function update(Request $request, Profile $profile): RedirectResponse
    {
        $this->authorize('view', $profile);

        $request->session()->put('current_profile_id', $profile->id);

        return back();
    }
}
