import { onBeforeUnmount, onMounted, ref } from 'vue';
import {
    onQueueChanged,
    pendingReadings,
    type PendingReading,
} from '@/lib/offlineQueue';

export function usePendingReadings() {
    const items = ref<PendingReading[]>([]);
    let stopQueueListener: (() => void) | undefined;

    const refresh = async (): Promise<void> => {
        if (typeof indexedDB === 'undefined') {
            return;
        }

        items.value = await pendingReadings();
    };

    onMounted(() => {
        void refresh();
        window.addEventListener('online', refresh);
        window.addEventListener('offline', refresh);
        stopQueueListener = onQueueChanged(() => {
            void refresh();
        });
    });

    onBeforeUnmount(() => {
        window.removeEventListener('online', refresh);
        window.removeEventListener('offline', refresh);
        stopQueueListener?.();
    });

    return { items, refresh };
}
