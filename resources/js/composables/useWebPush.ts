import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { destroy, store } from '@/routes/push-subscriptions';

function urlBase64ToArrayBuffer(base64: string): ArrayBuffer {
    const padding = '='.repeat((4 - (base64.length % 4)) % 4);
    const normalized = (base64 + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(normalized);
    const output = new Uint8Array(raw.length);

    for (let i = 0; i < raw.length; i++) {
        output[i] = raw.charCodeAt(i);
    }

    const copy = new ArrayBuffer(output.byteLength);
    new Uint8Array(copy).set(output);

    return copy;
}

export function useWebPush() {
    const page = usePage();
    const vapidPublicKey = computed(() => page.props.vapidPublicKey);

    const subscribe = async (): Promise<void> => {
        if (
            !vapidPublicKey.value ||
            !('serviceWorker' in navigator) ||
            !('PushManager' in window) ||
            typeof Notification === 'undefined'
        ) {
            return;
        }

        const permission = await Notification.requestPermission();

        if (permission !== 'granted') {
            return;
        }

        const registration = await navigator.serviceWorker.ready;
        let subscription = await registration.pushManager.getSubscription();

        if (subscription === null) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToArrayBuffer(
                    vapidPublicKey.value,
                ),
            });
        }

        const json = subscription.toJSON();

        router.post(
            store.url(),
            {
                endpoint: json.endpoint ?? '',
                keys: {
                    p256dh: json.keys?.p256dh ?? '',
                    auth: json.keys?.auth ?? '',
                },
            },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    const unsubscribe = async (): Promise<void> => {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            return;
        }

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription === null) {
            return;
        }

        router.delete(destroy.url(), {
            data: { endpoint: subscription.endpoint },
            preserveScroll: true,
            preserveState: true,
        });

        await subscription.unsubscribe();
    };

    return { vapidPublicKey, subscribe, unsubscribe };
}
