<?php

namespace App\Http\Middleware;

use App\Enums\ConsentType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHealthDataConsent
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->hasGrantedConsent(ConsentType::HealthData)) {
            return $next($request);
        }

        if ($request->routeIs([
            'consent.health-data',
            'consent.health-data.store',
            'logout',
            'about.data',
            'privacy',
            'terms',
            'shared.show',
            'shared.pdf',
            'shared.fhir',
            'shared.respond',
            'login',
            'login.store',
            'login.check-email',
            'login.magic',
            'register',
            'register.store',
            'invites.accept',
            'admin.*',
            'api.v1.auth.*',
            'api.v1.me',
            'api.v1.consent.*',
        ])) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'message' => 'Health data consent is required.',
                'consent_required' => true,
            ], 403);
        }

        return redirect()->route('consent.health-data');
    }
}
