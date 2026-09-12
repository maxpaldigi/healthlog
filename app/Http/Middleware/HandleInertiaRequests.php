<?php

namespace App\Http\Middleware;

use App\Support\AppLocale;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => $this->authProps($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'vapidPublicKey' => filled(config('webpush.vapid.public_key'))
                ? (string) config('webpush.vapid.public_key')
                : null,
            'locale' => app()->getLocale(),
            'locales' => AppLocale::options(),
            'translations' => AppLocale::catalog(app()->getLocale()),
        ];
    }

    /**
     * @return array{user: mixed, show_guide: bool, is_super_admin?: true}
     */
    private function authProps(Request $request): array
    {
        $auth = [
            'user' => $request->user(),
            'show_guide' => $request->user() !== null
                && $request->user()->onboarding_completed_at === null,
        ];

        if ($request->user()?->isSuperAdmin() === true) {
            $auth['is_super_admin'] = true;
        }

        return $auth;
    }
}
