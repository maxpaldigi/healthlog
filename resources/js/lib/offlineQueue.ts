const DB_NAME = 'healthyme';
const STORE = 'pending-readings';
const VERSION = 1;
const QUEUE_EVENT = 'healthyme-queue-changed';

export type PendingReading = {
    id: string;
    type: 'bp' | 'glucose';
    url: string;
    payload: Record<string, string>;
    summary: string;
    queuedAt: string;
};

function openDb(): Promise<IDBDatabase> {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, VERSION);

        request.onupgradeneeded = () => {
            const db = request.result;

            if (!db.objectStoreNames.contains(STORE)) {
                db.createObjectStore(STORE, { keyPath: 'id' });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () =>
            reject(request.error ?? new Error('IndexedDB failed'));
    });
}

function notifyQueueChanged(): void {
    window.dispatchEvent(new Event(QUEUE_EVENT));
}

export function onQueueChanged(listener: () => void): () => void {
    window.addEventListener(QUEUE_EVENT, listener);

    return () => window.removeEventListener(QUEUE_EVENT, listener);
}

export async function queueReading(item: PendingReading): Promise<void> {
    const db = await openDb();

    await new Promise<void>((resolve, reject) => {
        const tx = db.transaction(STORE, 'readwrite');
        tx.objectStore(STORE).put(item);
        tx.oncomplete = () => resolve();
        tx.onerror = () =>
            reject(tx.error ?? new Error('Could not save offline reading'));
    });

    notifyQueueChanged();
    await requestBackgroundSync();
}

export async function pendingReadings(): Promise<PendingReading[]> {
    if (typeof indexedDB === 'undefined') {
        return [];
    }

    const db = await openDb();

    const items = await new Promise<PendingReading[]>((resolve, reject) => {
        const tx = db.transaction(STORE, 'readonly');
        const request = tx.objectStore(STORE).getAll();
        request.onsuccess = () =>
            resolve((request.result as PendingReading[]) ?? []);
        request.onerror = () =>
            reject(
                request.error ?? new Error('Could not read offline readings'),
            );
    });

    return items.sort((a, b) => a.queuedAt.localeCompare(b.queuedAt));
}

export async function removePending(id: string): Promise<void> {
    const db = await openDb();

    await new Promise<void>((resolve, reject) => {
        const tx = db.transaction(STORE, 'readwrite');
        tx.objectStore(STORE).delete(id);
        tx.oncomplete = () => resolve();
        tx.onerror = () =>
            reject(tx.error ?? new Error('Could not remove offline reading'));
    });

    notifyQueueChanged();
}

export function formDataToPayload(form: FormData): Record<string, string> {
    const payload: Record<string, string> = {};

    form.forEach((value, key) => {
        if (typeof value === 'string') {
            payload[key] = value;
        }
    });

    return payload;
}

export function readingSummary(
    type: 'bp' | 'glucose',
    payload: Record<string, string>,
): string {
    if (type === 'bp') {
        return `Blood pressure ${payload.systolic}/${payload.diastolic}`;
    }

    return `Blood sugar ${payload.value}`;
}

async function requestBackgroundSync(): Promise<void> {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    try {
        const registration = await navigator.serviceWorker.ready;
        const sync = (
            registration as ServiceWorkerRegistration & {
                sync?: { register: (tag: string) => Promise<void> };
            }
        ).sync;

        await sync?.register('healthyme-sync-readings');
    } catch {
        // Background Sync is optional.
    }
}

let flushing = false;

export async function flushOfflineQueue(): Promise<void> {
    if (typeof navigator === 'undefined' || !navigator.onLine || flushing) {
        return;
    }

    const { router } = await import('@inertiajs/vue3');

    flushing = true;
    let uploadedCount = 0;

    try {
        const items = await pendingReadings();

        for (const item of items) {
            const uploaded = await new Promise<boolean>((resolve) => {
                router.post(item.url, item.payload, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(true),
                    onError: () => resolve(false),
                    onCancel: () => resolve(false),
                });
            });

            if (!uploaded) {
                break;
            }

            uploadedCount++;
            await removePending(item.id);
        }

        if (uploadedCount > 0) {
            router.reload({ preserveScroll: true });
        }
    } finally {
        flushing = false;
    }
}

export function initializeOfflineQueue(): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.addEventListener('online', () => {
        void flushOfflineQueue();
    });

    navigator.serviceWorker?.addEventListener(
        'message',
        (event: MessageEvent) => {
            if (event.data?.type === 'sync-readings') {
                void flushOfflineQueue();
            }
        },
    );

    if (navigator.onLine) {
        void flushOfflineQueue();
    }
}
