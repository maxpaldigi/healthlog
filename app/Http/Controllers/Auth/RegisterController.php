<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterUser;
use App\Actions\Auth\SendMagicLink;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Support\Timezones;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/Register', [
            'timezones' => Timezones::common(),
            'aliases' => Timezones::aliases(),
            'defaultTimezone' => '',
        ]);
    }

    public function store(
        RegisterUserRequest $request,
        RegisterUser $registerUser,
        SendMagicLink $sendMagicLink,
    ): RedirectResponse {
        $validated = $request->validated();

        $user = $registerUser->handle([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'timezone' => $validated['timezone'] ?? null,
            'detected_timezone' => $validated['detected_timezone'] ?? null,
            'glucose_unit' => $validated['glucose_unit'],
            'weight_unit' => $validated['weight_unit'],
        ]);

        $dispatch = $sendMagicLink->handle($user->email);

        if ($dispatch->devLoginUrl !== null) {
            session()->flash('dev_login_url', $dispatch->devLoginUrl);
        }

        if ($dispatch->mailFailed) {
            session()->flash('mail_failed', true);
        }

        return redirect()->route('login.check-email');
    }
}
