<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { FlaskConical, Pill, Scale, Trash2 } from '@lucide/vue';
import { onUnmounted, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy as destroyHba1c, store as storeHba1c } from '@/routes/hba1c';
import {
    destroy as destroyMedication,
    search as searchMedications,
    store as storeMedication,
    update as updateMedication,
} from '@/routes/medications';
import { systemDate, systemDateTimeLocal } from '@/lib/systemClock';
import { destroy as destroyWeight, store as storeWeight } from '@/routes/weights';

type MedicationSuggestion = { rxcui: string; name: string };

defineProps<{
    unit: string;
    defaults: { measured_at: string; tested_at: string };
    weights: { id: number; value: string; unit: string; measured_at: string }[];
    hba1c: { id: number; value: string; tested_at: string; lab_name: string | null }[];
    medications: {
        id: number;
        name: string;
        rxcui: string | null;
        dose: string | null;
        schedule: string | null;
        is_active: boolean;
    }[];
}>();

const medName = ref('');
const medRxcui = ref('');
const medResults = ref<MedicationSuggestion[]>([]);
const searchingMeds = ref(false);
let lastPickedName = '';
let medSearchTimer: number | null = null;

const runMedSearch = async (): Promise<void> => {
    const term = medName.value.trim();

    if (term.length < 2) {
        medResults.value = [];

        return;
    }

    searchingMeds.value = true;

    try {
        const response = await fetch(searchMedications.url({ query: { q: term } }), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const data = (await response.json()) as { results: MedicationSuggestion[] };
        medResults.value = data.results ?? [];
    } finally {
        searchingMeds.value = false;
    }
};

watch(medName, (value) => {
    if (value === lastPickedName) {
        medResults.value = [];

        return;
    }

    medRxcui.value = '';

    if (medSearchTimer !== null) {
        window.clearTimeout(medSearchTimer);
    }

    medSearchTimer = window.setTimeout(() => {
        void runMedSearch();
    }, 400);
});

const chooseMedication = (item: MedicationSuggestion): void => {
    lastPickedName = item.name;
    medName.value = item.name;
    medRxcui.value = item.rxcui;
    medResults.value = [];
};

onUnmounted(() => {
    if (medSearchTimer !== null) {
        window.clearTimeout(medSearchTimer);
    }
});
</script>

<template>
    <Head title="Clinic extras" />

    <div class="flex flex-1 flex-col gap-6">
        <header class="space-y-1">
            <h1 class="text-2xl font-semibold">Clinic extras</h1>
            <p class="text-muted-foreground text-base leading-relaxed">
                Optional notes your doctor may ask for. These also show on the
                visit report.
            </p>
        </header>

        <section class="hl-card space-y-4 px-4 py-4">
            <h2 class="flex items-center gap-2 text-lg font-semibold">
                <Scale class="size-5" aria-hidden="true" />
                Weight
            </h2>
            <Form
                v-bind="storeWeight.form()"
                class="grid gap-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="weight_value">Weight ({{ unit }})</Label>
                    <Input
                        id="weight_value"
                        name="value"
                        inputmode="decimal"
                        required
                        class="h-14 text-lg"
                    />
                    <InputError :message="errors.value" />
                </div>
                <div class="grid gap-2">
                    <Label for="weight_at">When</Label>
                    <Input
                        id="weight_at"
                        type="datetime-local"
                        name="measured_at"
                        :default-value="systemDateTimeLocal()"
                        required
                        class="h-14 text-lg"
                    />
                    <InputError :message="errors.measured_at" />
                </div>
                <Button type="submit" class="h-14" :disabled="processing">
                    Save weight
                </Button>
            </Form>
            <ul class="grid gap-2">
                <li
                    v-for="item in weights"
                    :key="item.id"
                    class="flex items-center justify-between gap-3 border-t pt-2"
                >
                    <span>
                        <span class="font-semibold tabular-nums">
                            {{ item.value }} {{ item.unit }}
                        </span>
                        <span class="text-muted-foreground block text-sm">
                            {{ item.measured_at }}
                        </span>
                    </span>
                    <Form v-bind="destroyWeight.form(item.id)">
                        <Button type="submit" variant="ghost" class="h-14 min-w-14">
                            <Trash2 class="size-4" aria-hidden="true" />
                            <span class="sr-only">Delete weight</span>
                        </Button>
                    </Form>
                </li>
            </ul>
        </section>

        <section class="hl-card space-y-4 px-4 py-4">
            <h2 class="flex items-center gap-2 text-lg font-semibold">
                <FlaskConical class="size-5" aria-hidden="true" />
                HbA1c
            </h2>
            <Form
                v-bind="storeHba1c.form()"
                class="grid gap-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="hba1c_value">Value (%)</Label>
                    <Input
                        id="hba1c_value"
                        name="value"
                        inputmode="decimal"
                        required
                        class="h-14 text-lg"
                    />
                    <InputError :message="errors.value" />
                </div>
                <div class="grid gap-2">
                    <Label for="tested_at">Test date</Label>
                    <Input
                        id="tested_at"
                        type="date"
                        name="tested_at"
                        :default-value="systemDate()"
                        required
                        class="h-14 text-lg"
                    />
                    <InputError :message="errors.tested_at" />
                </div>
                <div class="grid gap-2">
                    <Label for="lab_name">Lab name (optional)</Label>
                    <Input id="lab_name" name="lab_name" class="h-14 text-lg" />
                    <InputError :message="errors.lab_name" />
                </div>
                <Button type="submit" class="h-14" :disabled="processing">
                    Save HbA1c
                </Button>
            </Form>
            <ul class="grid gap-2">
                <li
                    v-for="item in hba1c"
                    :key="item.id"
                    class="flex items-center justify-between gap-3 border-t pt-2"
                >
                    <span>
                        <span class="font-semibold tabular-nums">{{ item.value }}%</span>
                        <span class="text-muted-foreground block text-sm">
                            {{ item.tested_at }}
                            <template v-if="item.lab_name"> · {{ item.lab_name }}</template>
                        </span>
                    </span>
                    <Form v-bind="destroyHba1c.form(item.id)">
                        <Button type="submit" variant="ghost" class="h-14 min-w-14">
                            <Trash2 class="size-4" aria-hidden="true" />
                            <span class="sr-only">Delete HbA1c</span>
                        </Button>
                    </Form>
                </li>
            </ul>
        </section>

        <section class="hl-card space-y-4 px-4 py-4">
            <h2 class="flex items-center gap-2 text-lg font-semibold">
                <Pill class="size-5" aria-hidden="true" />
                Medications
            </h2>
            <Form
                v-bind="storeMedication.form()"
                class="grid gap-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="med_name">Name</Label>
                    <Input
                        id="med_name"
                        v-model="medName"
                        name="name"
                        required
                        autocomplete="off"
                        class="h-14 text-lg"
                        data-test="medication-name"
                    />
                    <input type="hidden" name="rxcui" :value="medRxcui" />
                    <p class="text-muted-foreground text-base">
                        Pick a match from RxNorm, or save the name you typed.
                    </p>
                    <p v-if="searchingMeds" class="text-muted-foreground text-base">
                        Searching…
                    </p>
                    <ul
                        v-if="medResults.length > 0"
                        class="grid gap-2"
                        aria-label="Medication name matches"
                    >
                        <li v-for="item in medResults" :key="item.rxcui">
                            <button
                                type="button"
                                class="hl-card flex min-h-14 w-full cursor-pointer items-center px-4 py-3 text-left text-lg font-semibold"
                                :data-test="`medication-result-${item.rxcui}`"
                                @click="chooseMedication(item)"
                            >
                                {{ item.name }}
                            </button>
                        </li>
                    </ul>
                    <InputError :message="errors.name" />
                    <InputError :message="errors.rxcui" />
                </div>
                <div class="grid gap-2">
                    <Label for="med_dose">Dose (optional)</Label>
                    <Input id="med_dose" name="dose" class="h-14 text-lg" />
                </div>
                <div class="grid gap-2">
                    <Label for="med_schedule">When you take it (optional)</Label>
                    <Input id="med_schedule" name="schedule" class="h-14 text-lg" />
                </div>
                <label class="flex min-h-14 items-center gap-3 text-base">
                    <input type="checkbox" name="is_active" value="1" checked class="size-6" />
                    Active
                </label>
                <Button type="submit" class="h-14" :disabled="processing">
                    Save medication
                </Button>
            </Form>
            <ul class="grid gap-3">
                <li
                    v-for="item in medications"
                    :key="item.id"
                    class="space-y-2 border-t pt-3"
                >
                    <p>
                        <span class="font-semibold">{{ item.name }}</span>
                        <span v-if="item.dose"> · {{ item.dose }}</span>
                        <span v-if="item.schedule"> · {{ item.schedule }}</span>
                        <span v-if="!item.is_active" class="text-muted-foreground">
                            · stopped
                        </span>
                    </p>
                    <div class="flex gap-2">
                        <Form v-bind="updateMedication.form(item.id)">
                            <input type="hidden" name="name" :value="item.name" />
                            <input type="hidden" name="rxcui" :value="item.rxcui ?? ''" />
                            <input type="hidden" name="dose" :value="item.dose ?? ''" />
                            <input type="hidden" name="schedule" :value="item.schedule ?? ''" />
                            <input
                                type="hidden"
                                name="is_active"
                                :value="item.is_active ? '0' : '1'"
                            />
                            <Button type="submit" variant="outline" class="h-14">
                                {{ item.is_active ? 'Mark stopped' : 'Mark active' }}
                            </Button>
                        </Form>
                        <Form v-bind="destroyMedication.form(item.id)">
                            <Button type="submit" variant="ghost" class="h-14">
                                <Trash2 class="size-4" aria-hidden="true" />
                                Delete
                            </Button>
                        </Form>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>
