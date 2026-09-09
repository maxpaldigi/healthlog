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

const fileLabel = ref(
    props.expect === 'glucose'
        ? 'Take a photo of the sugar meter'
        : props.expect === 'bp'
          ? 'Take a photo of the BP monitor'
          : 'Take or choose a photo',
);

const title = computed(() => {
    if (props.expect === 'glucose') {
        return 'Scan sugar meter';
    }

    if (props.expect === 'bp') {
        return 'Scan BP monitor';
    }

    return 'Scan a photo';
});

const help = computed(() => {
    if (props.expect === 'glucose') {
        return 'Photograph the meter screen so the sugar number is large and clear. You will check it before it is saved.';
    }

    if (props.expect === 'bp') {
        return 'Photograph the monitor screen so the numbers are large and clear. You will check them before they are saved.';
    }

    return 'Photograph the device screen. You will check the numbers before they are saved.';
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
            <h1 class="text-2xl font-semibold">{{ title }}</h1>
            <p class="text-muted-foreground text-base leading-relaxed">
                {{ help }}
            </p>
        </div>

        <p
            v-if="status"
            class="rounded-2xl border border-amber-400 bg-amber-50 px-4 py-3 text-base leading-relaxed text-amber-950"
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
                class="hl-card border-primary/40 flex min-h-40 cursor-pointer flex-col items-center justify-center gap-3 border-2 border-dashed px-4 text-center text-xl font-semibold"
            >
                <Spinner v-if="processing" />
                <Camera v-else class="text-primary size-8" aria-hidden="true" />
                {{ processing ? 'Reading the photo…' : fileLabel }}
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

        <p class="text-muted-foreground text-center text-base">
            Prefer to type?
            <Link
                v-if="expect !== 'glucose'"
                :href="logBp()"
                class="text-foreground font-medium underline"
            >
                Log BP
            </Link>
            <template v-if="!expect"> or </template>
            <Link
                v-if="expect !== 'bp'"
                :href="logSugar()"
                class="text-foreground font-medium underline"
            >
                Log Sugar
            </Link>
        </p>
    </div>
</template>
