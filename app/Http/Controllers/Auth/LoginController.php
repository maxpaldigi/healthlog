<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SendMagicLink;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendMagicLinkRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/Login', [
            'status' => session('status'),
        ]);
    }

    public function store(SendMagicLinkRequest $request, SendMagicLink $sendMagicLink): RedirectResponse
    {
        $sendMagicLink->handle($request->validated('email'));

        return redirect()->route('login.check-email');
    }

    public function checkEmail(): Response
    {
        return Inertia::render('auth/CheckEmail', [
            'loginUrl' => session('dev_login_url'),
            'sendsRealMail' => ! in_array(config('mail.default'), ['log', 'array'], true),
            'mailFailed' => (bool) session('mail_failed'),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
