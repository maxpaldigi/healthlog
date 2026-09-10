<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import {
    Camera,
    Check,
    Clock,
    Droplet,
    Moon,
    Shuffle,
    Sunrise,
    Trash2,
    Utensils,
    UtensilsCrossed,
} from '@lucide/vue';
import { ref } from 'vue';
import type { Component } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    formDataToPayload,
    queueReading,
    readingSummary,
} from '@/lib/offlineQueue';
import { systemDateTimeLocal } from '@/lib/systemClock';
import { speak } from '@/composables/useReadAloud';
import { home } from '@/routes';
import { destroy, store, update } from '@/routes/glucose';
import { store as scanStore } from '@/routes/scan';

type Context = { value: string; label: string };

type Reading = {
    id: number;
    value: string;
    context: string;
    measured_at: string;
    note: string | null;
};

const props = defineProps<{
    reading: Reading | null;
    defaults: {
        measured_at: string;
        context: string;
        value?: string | null;
    } | null;
    contexts: Context[];
    unit: string;
    unitValue: string;
    source?: string;
    extractionNote?: string | null;
    scanStatus?: string | null;
}>();

const context = ref(
    props.reading?.context ?? props.defaults?.context ?? 'random',
);
const source = props.source ?? 'manual';
const showMore = ref(Boolean(props.reading?.note));

const formBind = props.reading ? update.form(props.reading.id) : store.form();
const clientUuid = crypto.randomUUID();

const maybeQueueOffline = (): boolean => {
    if (props.reading !== null || navigator.onLine) {
        return true;
    }

    const form = document.getElementById('log-sugar-form');

    if (!(form instanceof HTMLFormElement)) {
        return true;
    }

    const payload = formDataToPayload(new FormData(form));

    void queueReading({
        id: payload.client_uuid || clientUuid,
        type: 'glucose',
        url: store.url(),
        payload,
        summary: readingSummary('glucose', payload),
        queuedAt: new Date().toISOString(),
    }).then(() => {
        const spoken = readingSummary('glucose', payload);
        toast.success(
            'Saved on this phone. It will upload when you are online.',
        );
        speak(`${spoken}. Saved on this phone.`);
        router.visit(home.url());
    });

    return false;
};

const contextIcons: Record<string, Component> = {
    fasting: Sunrise,
    pre_meal: Utensils,
    post_meal_1h: UtensilsCrossed,
    post_meal_2h: UtensilsCrossed,
    bedtime: Moon,
    random: Shuffle,
};

const confirmDelete = (event: Event): void => {
    if (!window.confirm('Delete this reading?')) {
        event.preventDefault();
    }
};

const photoLabel = ref('Take a photo of the screen');

const onMeterPhoto = (event: Event): void => {
    const input = event.target as HTMLInputElement;

    if (!input.files?.length) {
        return;
    }

    photoLabel.value = 'Reading the photo…';
    input.form?.requestSubmit();
};
</script>

<template>
    <Head
        :title="
            reading
                ? 'Edit sugar'
                : source === 'photo'
                  ? 'Check sugar'
                  : 'Add blood sugar'
        "
    />

    <div class="flex flex-1 flex-col gap-6">
        <h1 id="log-sugar-heading" class="flex items-center gap-2 text-2xl font-semibold">
            <Droplet class="size-7" aria-hidden="true" />
            {{
                reading
                    ? 'Edit sugar'
                    : source === 'photo'
                      ? 'Check these numbers'
                      : 'Add blood sugar'
            }}
        </h1>
        <p
            v-if="!reading && source !== 'photo'"
            class="text-muted-foreground text-base leading-relaxed"
        >
            Type the one number from your sugar meter.
        </p>
        <p
            v-if="scanStatus"
            class="rounded-2xl border border-amber-400 bg-amber-50 px-4 py-3 text-base leading-relaxed text-amber-950"
            data-test="scan-status"
            role="alert"
        >
            {{ scanStatus }}
        </p>
        <p
            v-if="source === 'photo'"
            class="border-primary/30 bg-card rounded-2xl border px-4 py-3 text-base leading-relaxed"
            data-test="extraction-note"
        >
            {{ extractionNote || 'Check these numbers, then Save.' }}
        </p>

        <Form
            v-if="!reading && source !== 'photo'"
            v-bind="scanStore.form()"
            v-slot="{ processing }"
            class="grid gap-2"
        >
            <label
                class="hl-card border-primary/40 flex min-h-20 cursor-pointer items-center justify-center gap-3 border-2 border-dashed px-4 text-center text-lg font-semibold"
                :class="processing ? 'opacity-70' : ''"
            >
                <Spinner v-if="processing" />
                <Camera v-else class="text-primary size-6" aria-hidden="true" />
                {{ processing ? 'Reading the photo…' : photoLabel }}
                <input
                    type="file"
                    name="photo"
                    accept="image/jpeg,image/png,image/heic,image/heif"
                    capture="environment"
                    class="sr-only"
                    :disabled="processing"
                    :aria-label="photoLabel"
                    data-test="sugar-meter-photo"
                    @change="onMeterPhoto"
                />
            </label>
            <p class="text-muted-foreground text-center text-base">
                Or type the number below
            </p>
        </Form>

        <Form
            id="log-sugar-form"
            v-bind="formBind"
            v-slot="{ errors, processing }"
            class="flex flex-1 flex-col gap-6"
            aria-labelledby="log-sugar-heading"
            :on-before="maybeQueueOffline"
        >
            <input type="hidden" name="source" :value="source" />
            <input
                v-if="!reading"
                type="hidden"
                name="client_uuid"
                :value="clientUuid"
            />
            <div class="grid gap-2">
                <Label
                    for="value"
                    class="flex items-center justify-center gap-2 text-lg"
                >
                    <Droplet class="size-5" aria-hidden="true" />
                    Glucose ({{ unit }})
                </Label>
                <Input
                    id="value"
                    name="value"
                    inputmode="decimal"
                    autocomplete="off"
                    required
                    autofocus
                    aria-required="true"
                    :aria-invalid="errors.value ? true : undefined"
                    aria-describedby="value-error"
                    :default-value="reading?.value ?? defaults?.value ?? ''"
                    class="h-24 text-center text-5xl tabular-nums md:text-5xl"
                />
                <InputError id="value-error" :message="errors.value" />
            </div>

            <fieldset class="grid gap-2">
                <legend class="text-base font-medium">When was this?</legend>
                <input type="hidden" name="context" :value="context" />
                <div
                    class="grid grid-cols-2 gap-3"
                    role="radiogroup"
                    aria-label="When was this?"
                >
                    <Button
                        v-for="option in contexts"
                        :key="option.value"
                        type="button"
                        role="radio"
                        :aria-checked="context === option.value"
                        class="h-14 text-base"
                        :variant="
                            context === option.value ? 'default' : 'outline'
                        "
                        :data-test="`context-${option.value}`"
                        @click="context = option.value"
                    >
                        <component
                            :is="contextIcons[option.value] ?? Shuffle"
                            class="size-4"
                            aria-hidden="true"
                        />
                        {{ option.label }}
                    </Button>
                </div>
                <InputError :message="errors.context" />
            </fieldset>

            <div class="grid gap-2">
                <Label
                    for="measured_at"
                    class="flex items-center gap-2 text-base"
                >
                    <Clock class="size-5" aria-hidden="true" />
                    Time
                </Label>
                <Input
                    id="measured_at"
                    type="datetime-local"
                    name="measured_at"
                    :default-value="
                        reading?.measured_at ?? systemDateTimeLocal()
                    "
                    required
                    aria-required="true"
                    :aria-invalid="errors.measured_at ? true : undefined"
                    aria-describedby="measured-at-error"
                    class="h-14 text-lg md:text-lg"
                />
                <InputError id="measured-at-error" :message="errors.measured_at" />
            </div>

            <button
                v-if="!showMore"
                type="button"
                class="text-foreground inline-flex min-h-14 items-center justify-center text-base font-semibold underline-offset-4 hover:underline"
                data-test="sugar-more"
                @click="showMore = true"
            >
                Add a note
            </button>

            <div v-if="showMore" class="grid gap-2">
                <Label for="note" class="text-base">Note (optional)</Label>
                <Input
                    id="note"
                    name="note"
                    maxlength="240"
                    :default-value="reading?.note ?? ''"
                    :aria-invalid="errors.note ? true : undefined"
                    aria-describedby="note-error"
                    class="h-14 text-lg md:text-lg"
                    placeholder="Before breakfast, felt shaky…"
                />
                <InputError id="note-error" :message="errors.note" />
            </div>

            <Button
                type="submit"
                class="mt-auto h-16 w-full rounded-full text-xl"
                :disabled="processing"
                data-test="save-sugar"
            >
                <Spinner v-if="processing" />
                <Check v-else class="size-6" aria-hidden="true" />
                Save
            </Button>
        </Form>
    </div>

    <Form
        v-if="reading"
        v-bind="destroy.form(reading.id)"
        v-slot="{ processing }"
        class="mt-4"
    >
        <Button
            type="submit"
            variant="destructive"
            class="h-14 w-full text-base"
            :disabled="processing"
            data-test="delete-sugar"
            @click="confirmDelete"
        >
            <Trash2 class="size-5" aria-hidden="true" />
            Delete
        </Button>
    </Form>
</template>
