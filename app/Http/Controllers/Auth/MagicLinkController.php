<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ConsumeMagicLink;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class MagicLinkController extends Controller
{
    public function __invoke(Request $request, string $token, ConsumeMagicLink $consumeMagicLink): RedirectResponse|Response
    {
        // Mobile browsers: open the app without consuming the one-time token first.
        // Desktop / explicit web continue: consume and start a web session as before.
        if ($this->shouldOpenApp($request)) {
            $query = $request->query();
            unset($query['continue'], $query['signature'], $query['expires']);

            $appUrl = 'healthyme://login/magic/'.$token;
            $appQuery = array_merge($query, array_filter([
                'expires' => $request->query('expires'),
                'signature' => $request->query('signature'),
            ]));
            if ($appQuery !== []) {
                $appUrl .= '?'.http_build_query($appQuery);
            }

            $expiresAt = $request->query('expires')
                ? Carbon::createFromTimestamp((int) $request->query('expires'))
                : now()->addMinutes(15);

            $webContinueUrl = URL::temporarySignedRoute(
                'login.magic',
                $expiresAt,
                ['token' => $token, 'continue' => 'web'],
            );

            return Inertia::render('auth/OpenApp', [
                'appUrl' => $appUrl,
                'webContinueUrl' => $webContinueUrl,
            ]);
        }

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

    private function shouldOpenApp(Request $request): bool
    {
        if ($request->query('continue') === 'web') {
            return false;
        }

        $ua = (string) $request->userAgent();

        return (bool) preg_match('/Android|iPhone|iPad|iPod/i', $ua);
    }
}
