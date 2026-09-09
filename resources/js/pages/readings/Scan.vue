<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Camera } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import { create as logBp } from '@/routes/bp';
import { create as logSugar } from '@/routes/glucose';
import { store } from '@/routes/scan';

const props = defineProps<{
    status?: string | null;
    expect?: 'bp' | 'glucose' | null;
}>();

const fileLabel = ref('Take or choose a photo');

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
        return 'Photograph the meter screen so the sugar number is large and clear. You will check it before it is saved.';
    }

    if (props.expect === 'bp') {
        return 'Photograph the monitor screen so the numbers are large and clear. You will check them before they are saved.';
    }

    return 'Photograph the screen of a blood pressure monitor or a sugar meter. We can tell them apart from the numbers. You will check before anything is saved.';
});

const onFile = (event: Event): void => {
    const input = event.target as HTMLInputElement;

    if (!input.files?.length) {
        return;
    }

    fileLabel.value = input.files[0].name;
    input.form?.requestSubmit();
};
</script>

<template>
    <Head :title="title" />

    <div class="flex flex-1 flex-col gap-6">
        <div class="space-y-2">
            <h1 class="text-3xl font-bold leading-tight">
                Your numbers, from a photo
            </h1>
            <p class="text-muted-foreground text-lg leading-relaxed">
                {{ help }}
            </p>
        </div>

        <p
            v-if="status"
            class="rounded-2xl border border-amber-500 bg-amber-50 px-4 py-3 text-base leading-relaxed text-amber-950"
            data-test="scan-status"
            role="alert"
        >
            {{ status }}
        </p>

        <Form
            v-bind="store.form()"
            v-slot="{ processing, errors }"
            class="flex flex-col gap-3"
        >
            <input v-if="expect" type="hidden" name="expect" :value="expect" />
            <label
                class="bg-nav text-nav-foreground flex min-h-56 cursor-pointer flex-col items-center justify-center gap-4 rounded-[2rem] px-6 text-center shadow-[0_16px_40px_-18px_rgb(28_25_23_/_0.45)]"
            >
                <span
                    class="bg-cta text-cta-foreground flex size-20 items-center justify-center rounded-full"
                    aria-hidden="true"
                >
                    <Spinner v-if="processing" class="size-8" />
                    <Camera v-else class="size-10" />
                </span>
                <span class="text-2xl font-bold">
                    {{ processing ? 'Reading the photo…' : fileLabel }}
                </span>
                <span class="text-nav-muted text-base leading-relaxed">
                    Hold the phone so the screen fills the frame
                </span>
                <input
                    type="file"
                    name="photo"
                    accept="image/jpeg,image/png,image/heic,image/heif"
                    capture="environment"
                    class="sr-only"
                    :disabled="processing"
                    data-test="scan-photo"
                    @change="onFile"
                />
            </label>
            <InputError :message="errors.photo" />
        </Form>

        <p class="text-muted-foreground text-center text-base leading-relaxed">
            Prefer to type?
            <Link
                v-if="expect !== 'glucose'"
                :href="logBp()"
                class="text-foreground min-h-11 font-semibold underline"
            >
                Add blood pressure
            </Link>
            <template v-if="!expect"> or </template>
            <Link
                v-if="expect !== 'bp'"
                :href="logSugar()"
                class="text-foreground min-h-11 font-semibold underline"
            >
                Add blood sugar
            </Link>
        </p>
    </div>
</template>
