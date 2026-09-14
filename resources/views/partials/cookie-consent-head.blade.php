@php
    $measurementId = filled(config('services.google_analytics.measurement_id'))
        ? (string) config('services.google_analytics.measurement_id')
        : null;
    // Preview on local; Google tag only on the live site.
    $showCookieBanner = $measurementId !== null
        && (app()->isProduction() || app()->environment('local'));
    $loadGoogleTag = $measurementId !== null && app()->isProduction();
@endphp

@if ($loadGoogleTag)
    {{-- Google Consent Mode v2 defaults must run before the Google tag. --}}
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('consent', 'default', {
            analytics_storage: 'denied',
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            wait_for_update: 500
        });
        gtag('set', 'ads_data_redaction', true);
        gtag('set', 'url_passthrough', false);
        gtag('js', new Date());
        gtag('config', @json($measurementId), {
            anonymize_ip: true,
            send_page_view: false
        });

        (function () {
            try {
                var raw = localStorage.getItem('healthyme.cookie_consent');
                if (!raw) {
                    var legacy = localStorage.getItem('healthyme.analytics');
                    if (legacy === '1') {
                        raw = JSON.stringify({ v: 1, analytics: true });
                        localStorage.setItem('healthyme.cookie_consent', raw);
                    } else if (legacy === '0') {
                        raw = JSON.stringify({ v: 1, analytics: false });
                        localStorage.setItem('healthyme.cookie_consent', raw);
                    }
                }
                if (!raw) {
                    return;
                }
                var choice = JSON.parse(raw);
                if (choice && choice.analytics === true) {
                    gtag('consent', 'update', {
                        analytics_storage: 'granted',
                        ad_storage: 'denied',
                        ad_user_data: 'denied',
                        ad_personalization: 'denied'
                    });
                    gtag('event', 'page_view');
                }
            } catch (error) {}
        })();
    </script>
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $measurementId }}"></script>
@elseif ($showCookieBanner)
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
    </script>
@endif
