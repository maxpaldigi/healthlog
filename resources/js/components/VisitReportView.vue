<script setup lang="ts">
type SlotReading = {
    title: string;
    time_label: string;
    range_label: string | null;
    note: string | null;
} | null;

type Report = {
    profile: {
        name: string;
        timezone: string;
        glucose_unit: string;
        weight_unit: string;
    };
    range: { from: string; to: string; label: string };
    insights: {
        summary: {
            bp_count: number;
            glucose_count: number;
            average_bp: string | null;
            average_glucose: string | null;
            days_with_bp: number;
            days_with_three_bp: number;
        };
        summary_text: string;
        bp_slots: {
            key: string;
            label: string;
            count: number;
            average_systolic: number | null;
            average_diastolic: number | null;
        }[];
    };
    days: {
        date: string;
        date_label: string;
        bp_filled: number;
        slots: {
            morning: SlotReading;
            afternoon: SlotReading;
            evening: SlotReading;
        };
        sugar: {
            title: string;
            time_label: string;
            context_label: string;
            range_label: string | null;
            note: string | null;
        }[];
    }[];
    sugar_by_context: {
        key: string;
        label: string;
        count: number;
        average: string | null;
    }[];
    weights: { id: number; value: string; unit: string; measured_at: string }[];
    hba1c: {
        id: number;
        value: string;
        tested_at: string;
        lab_name: string | null;
    }[];
    medications: {
        id: number;
        name: string;
        dose: string | null;
        schedule: string | null;
        is_active: boolean;
    }[];
    disclaimer: string;
};

defineProps<{
    report: Report;
}>();

const slotOrder = ['morning', 'afternoon', 'evening'] as const;
const slotTitles = {
    morning: 'Morning',
    afternoon: 'Afternoon',
    evening: 'Evening',
};
</script>

<template>
    <div class="visit-report space-y-6 text-base">
        <header class="space-y-1">
            <h1 class="text-2xl font-semibold">Clinic visit report</h1>
            <p class="text-lg font-medium">{{ report.profile.name }}</p>
            <p class="text-muted-foreground">{{ report.range.label }}</p>
        </header>

        <section class="grid grid-cols-2 gap-3">
            <div class="hl-card px-3 py-3">
                <p class="text-muted-foreground text-sm">Average BP</p>
                <p class="mt-1 text-xl font-semibold tabular-nums">
                    {{ report.insights.summary.average_bp ?? '—' }}
                </p>
            </div>
            <div class="hl-card px-3 py-3">
                <p class="text-muted-foreground text-sm">Average sugar</p>
                <p class="mt-1 text-xl font-semibold tabular-nums">
                    {{ report.insights.summary.average_glucose ?? '—' }}
                </p>
            </div>
            <div class="hl-card px-3 py-3">
                <p class="text-muted-foreground text-sm">3-time days</p>
                <p class="mt-1 text-xl font-semibold tabular-nums">
                    {{ report.insights.summary.days_with_three_bp }}
                    <span class="text-muted-foreground text-base font-normal">
                        of {{ report.insights.summary.days_with_bp }}
                    </span>
                </p>
            </div>
            <div class="hl-card px-3 py-3">
                <p class="text-muted-foreground text-sm">Readings</p>
                <p class="mt-1 text-xl font-semibold tabular-nums">
                    {{ report.insights.summary.bp_count }} BP
                    <span class="text-muted-foreground text-base font-normal">
                        · {{ report.insights.summary.glucose_count }} sugar
                    </span>
                </p>
            </div>
        </section>

        <p>{{ report.insights.summary_text }}</p>

        <section>
            <h2 class="mb-2 text-lg font-semibold">Blood pressure by time of day</h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[28rem] border-collapse text-left">
                    <caption class="sr-only">
                        Morning, afternoon, and evening blood pressure
                    </caption>
                    <thead>
                        <tr class="bg-muted/70">
                            <th class="border px-2 py-2">Day</th>
                            <th
                                v-for="slot in slotOrder"
                                :key="slot"
                                class="border px-2 py-2"
                            >
                                {{ slotTitles[slot] }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="day in report.days" :key="day.date">
                            <th class="border px-2 py-2 font-medium">
                                {{ day.date_label }}
                            </th>
                            <td
                                v-for="slot in slotOrder"
                                :key="slot"
                                class="border px-2 py-2 align-top tabular-nums"
                            >
                                <template v-if="day.slots[slot]">
                                    <p class="font-semibold">
                                        {{ day.slots[slot]?.title }}
                                    </p>
                                    <p
                                        v-if="day.slots[slot]?.range_label"
                                        class="font-medium"
                                    >
                                        {{ day.slots[slot]?.range_label }}
                                    </p>
                                    <p class="text-muted-foreground text-sm">
                                        {{ day.slots[slot]?.time_label }}
                                    </p>
                                    <p
                                        v-if="day.slots[slot]?.note"
                                        class="mt-1 text-sm"
                                    >
                                        {{ day.slots[slot]?.note }}
                                    </p>
                                </template>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                        </tr>
                        <tr v-if="report.days.length === 0">
                            <td colspan="4" class="border px-2 py-3">
                                No blood pressure or sugar in this range.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-if="report.insights.bp_slots.some((slot) => slot.count > 0)">
            <h2 class="mb-2 text-lg font-semibold">Blood pressure averages</h2>
            <ul class="grid gap-1">
                <li
                    v-for="slot in report.insights.bp_slots"
                    :key="slot.key"
                    class="flex justify-between gap-3"
                >
                    <span>{{ slot.label }}</span>
                    <span class="tabular-nums">
                        <template v-if="slot.average_systolic !== null">
                            {{ slot.average_systolic }} / {{ slot.average_diastolic }}
                        </template>
                        <template v-else>—</template>
                    </span>
                </li>
            </ul>
        </section>

        <section v-if="report.days.some((day) => day.sugar.length > 0)">
            <h2 class="mb-2 text-lg font-semibold">Sugar readings</h2>
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-muted/70">
                        <th class="border px-2 py-2">Day</th>
                        <th class="border px-2 py-2">Value</th>
                        <th class="border px-2 py-2">When</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="day in report.days" :key="day.date">
                        <tr v-for="(item, index) in day.sugar" :key="`${day.date}-${index}`">
                            <td class="border px-2 py-2">
                                {{ index === 0 ? day.date_label : '' }}
                            </td>
                            <td class="border px-2 py-2 tabular-nums">
                                <span class="font-semibold">{{ item.title }}</span>
                                <span v-if="item.range_label" class="ml-2 font-medium">
                                    {{ item.range_label }}
                                </span>
                                <p v-if="item.note" class="text-sm">{{ item.note }}</p>
                            </td>
                            <td class="border px-2 py-2">
                                {{ item.time_label }} · {{ item.context_label }}
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </section>

        <section v-if="report.sugar_by_context.length > 0">
            <h2 class="mb-2 text-lg font-semibold">Sugar by when it was taken</h2>
            <ul class="grid gap-1">
                <li
                    v-for="row in report.sugar_by_context"
                    :key="row.key"
                    class="flex justify-between gap-3"
                >
                    <span>{{ row.label }} ({{ row.count }})</span>
                    <span class="tabular-nums">{{ row.average }}</span>
                </li>
            </ul>
        </section>

        <section v-if="report.weights.length > 0">
            <h2 class="mb-2 text-lg font-semibold">Weight</h2>
            <ul class="grid gap-1">
                <li
                    v-for="item in report.weights"
                    :key="item.id"
                    class="flex justify-between gap-3"
                >
                    <span>{{ item.measured_at }}</span>
                    <span class="tabular-nums">{{ item.value }} {{ item.unit }}</span>
                </li>
            </ul>
        </section>

        <section v-if="report.hba1c.length > 0">
            <h2 class="mb-2 text-lg font-semibold">HbA1c</h2>
            <ul class="grid gap-1">
                <li
                    v-for="item in report.hba1c"
                    :key="item.id"
                    class="flex justify-between gap-3"
                >
                    <span>
                        {{ item.tested_at }}
                        <span v-if="item.lab_name" class="text-muted-foreground">
                            · {{ item.lab_name }}
                        </span>
                    </span>
                    <span class="tabular-nums">{{ item.value }}%</span>
                </li>
            </ul>
        </section>

        <section v-if="report.medications.length > 0">
            <h2 class="mb-2 text-lg font-semibold">Medications</h2>
            <ul class="grid gap-1">
                <li v-for="item in report.medications" :key="item.id">
                    <span class="font-medium">{{ item.name }}</span>
                    <span v-if="item.dose"> · {{ item.dose }}</span>
                    <span v-if="item.schedule"> · {{ item.schedule }}</span>
                    <span v-if="!item.is_active" class="text-muted-foreground">
                        · stopped
                    </span>
                </li>
            </ul>
        </section>

        <p class="text-muted-foreground text-sm leading-relaxed">
            {{ report.disclaimer }}
        </p>
    </div>
</template>
