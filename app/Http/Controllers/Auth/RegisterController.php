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
            'defaultTimezone' => 'Asia/Kolkata',
        ]);
    }

    public function store(
        RegisterUserRequest $request,
        RegisterUser $registerUser,
        SendMagicLink $sendMagicLink,
    ): RedirectResponse {
        $user = $registerUser->handle($request->validated());

        $sendMagicLink->handle($user->email);

        return redirect()->route('login.check-email');
    }
}
