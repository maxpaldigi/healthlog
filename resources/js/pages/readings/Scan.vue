<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Camera, Image as ImageIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { compressPhoto } from '@/lib/compressPhoto';
import { postPhoto } from '@/lib/postPhoto';
import { create as logBp } from '@/routes/bp';
import { create as logSugar } from '@/routes/glucose';
import { store } from '@/routes/scan';

const props = defineProps<{
    status?: string | null;
    expect?: 'bp' | 'glucose' | null;
}>();

const photoInput = ref<HTMLInputElement | null>(null);
const preparing = ref(false);
const clientStatus = ref<string | null>(null);
const photoError = ref<string | null>(null);

const title = computed(() => {
    if (props.expect === 'glucose') {
        return 'Scan sugar meter';
    }

    if (props.expect === 'bp') {
        return 'Scan BP monitor';
    }

    return 'Take a photo';
});

const help = computed(() => {
    if (props.expect === 'glucose') {
        return 'Photograph the sugar number so it is large and clear, or choose a photo from this computer.';
    }

    if (props.expect === 'bp') {
        return 'Photograph the cuff numbers so they are large and clear, or choose a photo from this computer.';
    }

    return 'Photograph the blood pressure or sugar screen, or choose a photo. You will check the numbers before they are saved.';
});

const openPicker = (useCamera: boolean): void => {
    const input = photoInput.value;

    if (input === null || preparing.value) {
        return;
    }

    if (useCamera) {
        input.setAttribute('capture', 'environment');
    } else {
        input.removeAttribute('capture');
    }

    input.click();
};

const showReadFailed = (): void => {
    clientStatus.value =
        "Couldn't read that photo. Try another picture, or type the numbers.";
};

const onFile = async (event: Event): Promise<void> => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';

    if (!file || preparing.value) {
        return;
    }

    clientStatus.value = null;
    photoError.value = null;
    preparing.value = true;

    try {
        const compressed = await compressPhoto(file);

        postPhoto(
            store.url(),
            compressed,
            props.expect ? { expect: props.expect } : {},
            {
                onFinish: () => {
                    preparing.value = false;
                },
                onError: (errors) => {
                    preparing.value = false;
                    photoError.value = errors.photo ?? null;

                    if (!errors.photo) {
                        showReadFailed();
                    }
                },
                onFail: () => {
                    preparing.value = false;
                    showReadFailed();
                },
            },
        );
    } catch {
        preparing.value = false;
        clientStatus.value =
            "Couldn't prepare that photo. Try another picture, or type the numbers.";
    }
};
</script>

<template>
    <Head :title="title" />

    <div class="flex flex-1 flex-col gap-6">
        <div class="space-y-2">
            <h1 class="text-3xl font-bold leading-tight">
                Photograph the screen
            </h1>
            <p class="text-muted-foreground text-lg leading-relaxed">
                {{ help }}
            </p>
        </div>

        <p
            v-if="status || clientStatus"
            class="rounded-2xl border border-amber-500 bg-amber-50 px-4 py-3 text-base leading-relaxed text-amber-950"
            data-test="scan-status"
            role="alert"
        >
            {{ clientStatus || status }}
        </p>

        <div class="flex flex-col gap-3">
            <input
                ref="photoInput"
                type="file"
                accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
                class="sr-only"
                aria-label="Meter photo"
                data-test="scan-photo"
                @change="onFile"
            />

            <div
                class="bg-nav text-nav-foreground flex min-h-56 flex-col items-center justify-center gap-4 rounded-[2rem] px-6 py-8 text-center shadow-[0_16px_40px_-18px_rgb(28_25_23_/_0.45)]"
            >
                <span
                    class="bg-cta text-cta-foreground flex size-20 items-center justify-center rounded-full"
                    aria-hidden="true"
                >
                    <Spinner v-if="preparing" class="size-8" />
                    <Camera v-else class="size-10" />
                </span>
                <span class="text-2xl font-bold">
                    {{
                        preparing
                            ? 'Reading the photo…'
                            : 'Hold the screen so the numbers fill the frame'
                    }}
                </span>
                <span
                    v-if="preparing"
                    class="text-nav-muted text-base leading-relaxed"
                >
                    This usually takes a few seconds. Stay on this page.
                </span>
                <div v-else class="flex w-full max-w-sm flex-col gap-3">
                    <Button
                        type="button"
                        class="h-16 w-full rounded-full text-lg"
                        :disabled="preparing"
                        data-test="scan-camera"
                        @click="openPicker(true)"
                    >
                        <Camera class="size-5" aria-hidden="true" />
                        Take a photo
                    </Button>
                    <Button
                        type="button"
                        variant="secondary"
                        class="h-16 w-full rounded-full text-lg"
                        :disabled="preparing"
                        data-test="scan-file"
                        @click="openPicker(false)"
                    >
                        <ImageIcon class="size-5" aria-hidden="true" />
                        Choose from this computer
                    </Button>
                </div>
            </div>
            <InputError :message="photoError ?? undefined" />
        </div>

        <p class="text-muted-foreground text-center text-base leading-relaxed">
            Prefer to type?
            <Link
                v-if="expect !== 'glucose'"
                :href="logBp()"
                class="text-foreground inline-flex min-h-14 items-center font-semibold underline"
            >
                Add blood pressure
            </Link>
            <template v-if="!expect"> or </template>
            <Link
                v-if="expect !== 'bp'"
                :href="logSugar()"
                class="text-foreground inline-flex min-h-14 items-center font-semibold underline"
            >
                Add blood sugar
            </Link>
        </p>
    </div>
</template>
