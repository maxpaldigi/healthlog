<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\EnterDemoAccount;
use App\Actions\Auth\SendMagicLink;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendMagicLinkRequest;
use App\Support\DemoAccount;
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
            'demoEmail' => DemoAccount::instantLoginEnabled() ? DemoAccount::email() : null,
        ]);
    }

    public function store(
        SendMagicLinkRequest $request,
        SendMagicLink $sendMagicLink,
        EnterDemoAccount $enterDemoAccount,
    ): RedirectResponse {
        $email = $request->validated('email');

        if (DemoAccount::instantLoginEnabled() && DemoAccount::matches($email)) {
            $enterDemoAccount->handle();
            $request->session()->regenerate();

            return redirect()->route('home');
        }

        $sendMagicLink->handle($email);

        return redirect()->route('login.check-email');
    }

    public function checkEmail(): Response
    {
        return Inertia::render('auth/CheckEmail', [
            'loginUrl' => session('dev_login_url'),
            'sendsRealMail' => ! in_array(config('mail.default'), ['log', 'array'], true),
            'mailFailed' => (bool) session('mail_failed'),
            'demoLogin' => DemoAccount::instantLoginEnabled(),
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
