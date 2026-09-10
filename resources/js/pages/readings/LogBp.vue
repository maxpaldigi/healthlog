<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import {
    Activity,
    Check,
    ChevronsDown,
    ChevronsUp,
    Circle,
    Clock,
    Dumbbell,
    Heart,
    Pill,
    Trash2,
    Zap,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import type { Component } from 'vue';
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
import { destroy, store, update } from '@/routes/bp';

type Tag = { value: string; label: string };

type Reading = {
    id: number;
    systolic: number;
    diastolic: number;
    pulse: number | null;
    measured_at: string;
    tag: string;
    note: string | null;
};

const props = defineProps<{
    reading: Reading | null;
    defaults: {
        measured_at: string;
        tag: string;
        systolic?: number | null;
        diastolic?: number | null;
        pulse?: number | null;
    } | null;
    tags: Tag[];
    source?: string;
    extractionNote?: string | null;
    scanStatus?: string | null;
}>();

const systolic = ref(
    props.reading?.systolic?.toString() ??
        props.defaults?.systolic?.toString() ??
        '',
);
const diastolic = ref(
    props.reading?.diastolic?.toString() ??
        props.defaults?.diastolic?.toString() ??
        '',
);
const pulse = ref(
    props.reading?.pulse?.toString() ?? props.defaults?.pulse?.toString() ?? '',
);
const tag = ref(props.reading?.tag ?? props.defaults?.tag ?? 'none');
const source = props.source ?? 'manual';
const showMore = ref(
    Boolean(props.reading?.note) ||
        ((props.reading?.tag ?? props.defaults?.tag ?? 'none') !== 'none'),
);

const formBind = props.reading ? update.form(props.reading.id) : store.form();
const clientUuid = crypto.randomUUID();

const maybeQueueOffline = (): boolean => {
    if (props.reading !== null || navigator.onLine) {
        return true;
    }

    const form = document.getElementById('log-bp-form');

    if (!(form instanceof HTMLFormElement)) {
        return true;
    }

    const payload = formDataToPayload(new FormData(form));

    void queueReading({
        id: payload.client_uuid || clientUuid,
        type: 'bp',
        url: store.url(),
        payload,
        summary: readingSummary('bp', payload),
        queuedAt: new Date().toISOString(),
    }).then(() => {
        const spoken = readingSummary('bp', payload);
        toast.success(
            'Saved on this phone. It will upload when you are online.',
        );
        speak(`${spoken}. Saved on this phone.`);
        router.visit(home.url());
    });

    return false;
};

watch(systolic, (value) => {
    if (value.replace(/\D/g, '').length >= 3) {
        document.getElementById('diastolic')?.focus();
    }
});

watch(diastolic, (value) => {
    if (value.replace(/\D/g, '').length >= 3) {
        document.getElementById('pulse')?.focus();
    }
});

const tagIcons: Record<string, Component> = {
    none: Circle,
    before_meds: Pill,
    after_meds: Pill,
    after_exercise: Dumbbell,
    stressed: Zap,
};

const confirmDelete = (event: Event): void => {
    if (!window.confirm('Delete this reading?')) {
        event.preventDefault();
    }
};
</script>

<template>
    <Head
        :title="
            reading
                ? 'Edit BP'
                : source === 'photo'
                  ? 'Check BP'
                  : 'Add blood pressure'
        "
    />

    <Form
        id="log-bp-form"
        v-bind="formBind"
        v-slot="{ errors, processing }"
        class="flex flex-1 flex-col gap-6"
        aria-labelledby="log-bp-heading"
        :on-before="maybeQueueOffline"
    >
        <h1 id="log-bp-heading" class="flex items-center gap-2 text-2xl font-semibold">
            <Activity class="size-7" aria-hidden="true" />
            {{
                reading
                    ? 'Edit BP'
                    : source === 'photo'
                      ? 'Check these numbers'
                      : 'Add blood pressure'
            }}
        </h1>
        <p
            v-if="scanStatus"
            class="rounded-2xl border border-amber-400 bg-amber-50 px-4 py-3 text-base leading-relaxed text-amber-950"
            role="alert"
        >
            {{ scanStatus }}
        </p>
        <p
            v-if="!reading && source !== 'photo'"
            class="text-muted-foreground text-base leading-relaxed"
        >
            Type the top and bottom numbers from the cuff.
        </p>
        <p
            v-if="source === 'photo'"
            class="border-primary/30 bg-card rounded-2xl border px-4 py-3 text-base leading-relaxed"
            data-test="extraction-note"
        >
            {{ extractionNote || 'Check these numbers, then Save.' }}
        </p>
        <input type="hidden" name="source" :value="source" />
        <input
            v-if="!reading"
            type="hidden"
            name="client_uuid"
            :value="clientUuid"
        />
        <div class="grid grid-cols-3 gap-3">
            <div class="grid gap-2">
                <Label
                    for="systolic"
                    class="flex flex-col items-center gap-1 text-center text-base leading-tight"
                >
                    <ChevronsUp class="size-5" aria-hidden="true" />
                    Top
                    <span class="sr-only">, systolic</span>
                </Label>
                <Input
                    id="systolic"
                    v-model="systolic"
                    name="systolic"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    autocomplete="off"
                    required
                    autofocus
                    aria-required="true"
                    :aria-invalid="errors.systolic ? true : undefined"
                    :aria-describedby="
                        errors.systolic ? 'systolic-error' : 'systolic-hint'
                    "
                    class="h-20 text-center text-3xl tabular-nums md:text-3xl"
                />
                <p id="systolic-hint" class="sr-only">
                    Top number from the cuff.
                </p>
                <InputError id="systolic-error" :message="errors.systolic" />
            </div>
            <div class="grid gap-2">
                <Label
                    for="diastolic"
                    class="flex flex-col items-center gap-1 text-center text-base leading-tight"
                >
                    <ChevronsDown class="size-5" aria-hidden="true" />
                    Bottom
                    <span class="sr-only">, diastolic</span>
                </Label>
                <Input
                    id="diastolic"
                    v-model="diastolic"
                    name="diastolic"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    autocomplete="off"
                    required
                    aria-required="true"
                    :aria-invalid="errors.diastolic ? true : undefined"
                    :aria-describedby="
                        errors.diastolic ? 'diastolic-error' : 'diastolic-hint'
                    "
                    class="h-20 text-center text-3xl tabular-nums md:text-3xl"
                />
                <p id="diastolic-hint" class="sr-only">
                    Bottom number from the cuff.
                </p>
                <InputError id="diastolic-error" :message="errors.diastolic" />
            </div>
            <div class="grid gap-2">
                <Label
                    for="pulse"
                    class="flex flex-col items-center gap-1 text-center text-base leading-tight"
                >
                    <Heart class="size-5" aria-hidden="true" />
                    Pulse
                    <span class="sr-only">, optional</span>
                </Label>
                <Input
                    id="pulse"
                    v-model="pulse"
                    name="pulse"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    autocomplete="off"
                    :aria-invalid="errors.pulse ? true : undefined"
                    aria-describedby="pulse-error"
                    class="h-20 text-center text-3xl tabular-nums md:text-3xl"
                />
                <InputError id="pulse-error" :message="errors.pulse" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="measured_at" class="flex items-center gap-2 text-base">
                <Clock class="size-5" aria-hidden="true" />
                When
            </Label>
            <Input
                id="measured_at"
                type="datetime-local"
                name="measured_at"
                :default-value="reading?.measured_at ?? systemDateTimeLocal()"
                required
                aria-required="true"
                :aria-invalid="errors.measured_at ? true : undefined"
                aria-describedby="measured-at-error"
                class="h-14 text-lg md:text-lg"
            />
            <InputError id="measured-at-error" :message="errors.measured_at" />
        </div>

        <input type="hidden" name="tag" :value="tag" />

        <button
            v-if="!showMore"
            type="button"
            class="text-foreground inline-flex min-h-14 items-center justify-center text-base font-semibold underline-offset-4 hover:underline"
            data-test="bp-more"
            @click="showMore = true"
        >
            Add a tag or note
        </button>

        <fieldset v-if="showMore" class="grid gap-2">
            <legend class="text-base font-medium">Optional tag</legend>
            <div
                class="flex flex-wrap gap-2"
                role="radiogroup"
                aria-label="Optional tag"
            >
                <Button
                    v-for="option in tags"
                    :key="option.value"
                    type="button"
                    role="radio"
                    :aria-checked="tag === option.value"
                    class="h-14 text-base"
                    :variant="tag === option.value ? 'default' : 'outline'"
                    @click="tag = option.value"
                >
                    <component
                        :is="tagIcons[option.value] ?? Circle"
                        class="size-4"
                        aria-hidden="true"
                    />
                    {{ option.label }}
                </Button>
            </div>
            <InputError :message="errors.tag" />
        </fieldset>

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
                placeholder="Felt dizzy, after a walk…"
            />
            <InputError id="note-error" :message="errors.note" />
        </div>

        <Button
            type="submit"
            class="mt-auto h-16 w-full rounded-full text-xl"
            :disabled="processing"
            data-test="save-bp"
        >
            <Spinner v-if="processing" />
            <Check v-else class="size-6" aria-hidden="true" />
            Save
        </Button>
    </Form>

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
            data-test="delete-bp"
            @click="confirmDelete"
        >
            <Trash2 class="size-5" aria-hidden="true" />
            Delete
        </Button>
    </Form>
</template>
