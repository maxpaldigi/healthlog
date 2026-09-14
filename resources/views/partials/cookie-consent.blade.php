@php
    $measurementId = filled(config('services.google_analytics.measurement_id'))
        ? (string) config('services.google_analytics.measurement_id')
        : null;
    $showCookieBanner = $measurementId !== null
        && (app()->isProduction() || app()->environment('local'));
    $privacyUrl = url('/privacy');
@endphp

@if ($showCookieBanner)
    {{-- Visible until a saved choice hides it. Fail open so a script error never leaves consent impossible. --}}
    <div
        id="cookie-consent"
        class="fixed inset-x-0 bottom-0 z-[100] p-4 pb-[max(1rem,env(safe-area-inset-bottom))]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cookie-consent-title"
        aria-describedby="cookie-consent-copy"
        data-test="cookie-consent"
    >
        <div
            class="mx-auto w-full max-w-lg rounded-[1.5rem] border border-[#d6cfc4] bg-[#f6f0e8] p-5 shadow-[0_-8px_32px_rgba(26,22,20,0.18)]"
        >
            <p id="cookie-consent-title" class="text-2xl font-bold text-[#1a1614]">
                Cookies
            </p>
            <p id="cookie-consent-copy" class="mt-2 text-base leading-relaxed text-[#4a433c]">
                We use necessary cookies so you can sign in and keep your log.
                Optional cookies help us count page visits with Google Analytics.
                No blood pressure, sugar, or other health readings are sent.
                Ads cookies stay off.
            </p>

            <ul class="mt-4 space-y-2 text-base leading-relaxed text-[#1a1614]">
                <li class="flex gap-3">
                    <span class="mt-1 size-2.5 shrink-0 rounded-full bg-[#2A3B8F]" aria-hidden="true"></span>
                    <span><span class="font-bold">Necessary</span> — always on. Login and security.</span>
                </li>
                <li class="flex gap-3">
                    <span class="mt-1 size-2.5 shrink-0 rounded-full bg-[#c45c26]" aria-hidden="true"></span>
                    <span><span class="font-bold">Analytics</span> — optional. Visit counts only.</span>
                </li>
            </ul>

            <p class="mt-3 text-base">
                <a
                    href="{{ $privacyUrl }}"
                    class="font-semibold text-[#2A3B8F] underline underline-offset-4"
                >
                    Privacy notice
                </a>
            </p>

            <div class="mt-5 flex flex-col gap-3">
                <button
                    type="button"
                    id="cookie-consent-accept"
                    class="flex min-h-14 w-full cursor-pointer items-center justify-center rounded-full bg-[#2A3B8F] px-4 text-lg font-bold text-white"
                    data-test="cookie-consent-accept"
                >
                    Accept analytics cookies
                </button>
                <button
                    type="button"
                    id="cookie-consent-reject"
                    class="flex min-h-14 w-full cursor-pointer items-center justify-center rounded-full border-2 border-[#1a1614] bg-white px-4 text-lg font-bold text-[#1a1614]"
                    data-test="cookie-consent-reject"
                >
                    Reject analytics cookies
                </button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var storageKey = 'healthyme.cookie_consent';
            var banner = document.getElementById('cookie-consent');
            if (!banner) {
                return;
            }

            function readChoice() {
                try {
                    var raw = localStorage.getItem(storageKey);
                    if (!raw) {
                        var legacy = localStorage.getItem('healthyme.analytics');
                        if (legacy === '1') {
                            return { analytics: true };
                        }
                        if (legacy === '0') {
                            return { analytics: false };
                        }
                        return null;
                    }
                    return JSON.parse(raw);
                } catch (error) {
                    return null;
                }
            }

            function writeChoice(analytics) {
                try {
                    localStorage.setItem(storageKey, JSON.stringify({
                        v: 1,
                        analytics: analytics === true,
                        updated_at: new Date().toISOString()
                    }));
                    localStorage.setItem('healthyme.analytics', analytics ? '1' : '0');
                } catch (error) {}
            }

            function applyConsent(analytics) {
                if (typeof gtag !== 'function') {
                    return;
                }
                gtag('consent', 'update', {
                    analytics_storage: analytics ? 'granted' : 'denied',
                    ad_storage: 'denied',
                    ad_user_data: 'denied',
                    ad_personalization: 'denied'
                });
                if (analytics) {
                    gtag('event', 'page_view');
                }
            }

            function hide() {
                banner.style.display = 'none';
                banner.setAttribute('hidden', 'hidden');
            }

            function show() {
                banner.removeAttribute('hidden');
                banner.style.display = '';
            }

            var existing = readChoice();
            if (existing !== null && typeof existing.analytics === 'boolean') {
                hide();
            } else {
                show();
            }

            var accept = document.getElementById('cookie-consent-accept');
            var reject = document.getElementById('cookie-consent-reject');

            if (accept) {
                accept.addEventListener('click', function () {
                    writeChoice(true);
                    applyConsent(true);
                    hide();
                });
            }

            if (reject) {
                reject.addEventListener('click', function () {
                    writeChoice(false);
                    applyConsent(false);
                    hide();
                });
            }

            window.healthymeOpenCookieConsent = function () {
                show();
            };
        })();
    </script>
@endif
