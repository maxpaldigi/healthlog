import { router } from '@inertiajs/vue3';

declare global {
    interface Window {
        gtag?: (...args: unknown[]) => void;
        dataLayer?: unknown[];
    }
}

/**
 * Send a page view on Inertia navigations when Google Analytics is loaded
 * and the person has accepted visit counting.
 */
export function initializeAnalytics(): void {
    if (typeof window.gtag !== 'function') {
        return;
    }

    router.on('navigate', (event) => {
        try {
            if (window.localStorage.getItem('healthyme.analytics') !== '1') {
                return;
            }
        } catch {
            return;
        }

        window.gtag?.('event', 'page_view', {
            page_path: event.detail.page.url,
            page_title: document.title,
        });
    });
}
