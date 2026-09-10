<script setup lang="ts">
import { Download, Share, X } from '@lucide/vue';
import { onMounted, onBeforeUnmount, ref } from 'vue';
import { Button } from '@/components/ui/button';

const STORAGE_KEY = 'healthyme-install-dismissed';

type BeforeInstallPromptEvent = Event & {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
};

const deferredPrompt = ref<BeforeInstallPromptEvent | null>(null);
const showAndroid = ref(false);
const showIos = ref(false);

const isStandalone = (): boolean => {
    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        ('standalone' in navigator &&
            (navigator as Navigator & { standalone?: boolean }).standalone ===
                true)
    );
};

const isIos = (): boolean => /iphone|ipad|ipod/i.test(navigator.userAgent);

const dismissed = (): boolean => localStorage.getItem(STORAGE_KEY) === '1';

const dismiss = (): void => {
    localStorage.setItem(STORAGE_KEY, '1');
    showAndroid.value = false;
    showIos.value = false;
    deferredPrompt.value = null;
};

const install = async (): Promise<void> => {
    const promptEvent = deferredPrompt.value;

    if (!promptEvent) {
        return;
    }

    await promptEvent.prompt();
    await promptEvent.userChoice;
    dismiss();
};

const onBeforeInstall = (event: Event): void => {
    event.preventDefault();
    deferredPrompt.value = event as BeforeInstallPromptEvent;
    showAndroid.value = true;
};

onMounted(() => {
    if (dismissed() || isStandalone()) {
        return;
    }

    if (isIos()) {
        showIos.value = true;

        return;
    }

    window.addEventListener('beforeinstallprompt', onBeforeInstall);
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeinstallprompt', onBeforeInstall);
});
</script>

<template>
    <div
        v-if="showAndroid || showIos"
        class="no-print pointer-events-none fixed inset-x-0 bottom-[5.75rem] z-40 mx-auto w-full max-w-md px-4"
        data-test="install-banner"
    >
        <section
            class="border-border bg-card pointer-events-auto rounded-2xl border px-4 py-4 shadow-[0_12px_28px_-12px_rgb(28_25_23_/_0.45)]"
        >
            <div class="flex items-start gap-3">
                <p class="flex-1 text-base leading-relaxed">
                    <span v-if="showAndroid">
                        Add HealthyMe to your Home Screen so you can open it
                        without the browser.
                    </span>
                    <span v-else>
                        On iPhone: tap Share, then Add to Home Screen.
                    </span>
                </p>
                <Button
                    type="button"
                    variant="ghost"
                    class="size-11 shrink-0"
                    aria-label="Dismiss"
                    @click="dismiss"
                >
                    <X class="size-5" aria-hidden="true" />
                </Button>
            </div>
            <div class="mt-3 flex gap-2">
                <Button
                    v-if="showAndroid"
                    type="button"
                    class="h-12 flex-1 text-base"
                    @click="install"
                >
                    <Download class="size-5" aria-hidden="true" />
                    Add to Home Screen
                </Button>
                <p
                    v-else
                    class="text-muted-foreground flex min-h-11 flex-1 items-center gap-2 text-base"
                >
                    <Share class="size-5 shrink-0" aria-hidden="true" />
                    Use the Share button at the bottom of Safari.
                </p>
            </div>
        </section>
    </div>
</template>
