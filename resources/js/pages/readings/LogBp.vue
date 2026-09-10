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
        toast.success(
            'Saved on this phone. It will upload when you are online.',
        );
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
        :on-before="maybeQueueOffline"
    >
        <h1 class="flex items-center gap-2 text-2xl font-semibold">
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
            Type the top and bottom numbers from your arm cuff. Doctors often
            want 3 readings a day: morning, afternoon, and evening.
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
                    Systolic
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
                    class="h-20 text-center text-3xl tabular-nums md:text-3xl"
                />
                <InputError :message="errors.systolic" />
            </div>
            <div class="grid gap-2">
                <Label
                    for="diastolic"
                    class="flex flex-col items-center gap-1 text-center text-base leading-tight"
                >
                    <ChevronsDown class="size-5" aria-hidden="true" />
                    Diastolic
                </Label>
                <Input
                    id="diastolic"
                    v-model="diastolic"
                    name="diastolic"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    autocomplete="off"
                    required
                    class="h-20 text-center text-3xl tabular-nums md:text-3xl"
                />
                <InputError :message="errors.diastolic" />
            </div>
            <div class="grid gap-2">
                <Label
                    for="pulse"
                    class="flex flex-col items-center gap-1 text-center text-base leading-tight"
                >
                    <Heart class="size-5" aria-hidden="true" />
                    Pulse
                </Label>
                <Input
                    id="pulse"
                    v-model="pulse"
                    name="pulse"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    autocomplete="off"
                    class="h-20 text-center text-3xl tabular-nums md:text-3xl"
                />
                <InputError :message="errors.pulse" />
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
                class="h-14 text-lg md:text-lg"
            />
            <InputError :message="errors.measured_at" />
        </div>

        <div class="grid gap-2">
            <p class="text-base font-medium">Optional tag</p>
            <input type="hidden" name="tag" :value="tag" />
            <div class="flex flex-wrap gap-2">
                <Button
                    v-for="option in tags"
                    :key="option.value"
                    type="button"
                    class="h-12 text-base"
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
        </div>

        <div class="grid gap-2">
            <Label for="note" class="text-base">Note (optional)</Label>
            <Input
                id="note"
                name="note"
                maxlength="240"
                :default-value="reading?.note ?? ''"
                class="h-14 text-lg md:text-lg"
                placeholder="Felt dizzy, after a walk…"
            />
            <InputError :message="errors.note" />
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
