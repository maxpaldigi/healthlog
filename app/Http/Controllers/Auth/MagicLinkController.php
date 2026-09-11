<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ConsumeMagicLink;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MagicLinkController extends Controller
{
    public function __invoke(Request $request, string $token, ConsumeMagicLink $consumeMagicLink): RedirectResponse
    {
        $user = $consumeMagicLink->handle($request, $token);

        if ($user === null) {
            return redirect()
                ->route('login')
                ->with('status', 'This login link is invalid or has expired. Please request a new one.');
        }

        $home = $user->isSuperAdmin()
            ? route('admin.dashboard')
            : route('home');

        return redirect()->intended($home);
    }
}
