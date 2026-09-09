<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Activity, Droplet, Printer } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import HistoryDateRange from '@/components/HistoryDateRange.vue';
import TrendBarChart from '@/components/TrendBarChart.vue';
import TrendLineChart from '@/components/TrendLineChart.vue';
import { create as logBp } from '@/routes/bp';
import { create as logSugar } from '@/routes/glucose';
import { report, trends } from '@/routes';

type Filters = {
    from: string;
    to: string;
    from_label: string;
    to_label: string;
    range_label: string;
    today: string;
    earliest: string;
    preset: string | null;
};

type Insights = {
    summary: {
        bp_count: number;
        glucose_count: number;
        latest_bp: string | null;
        latest_bp_range_label: string | null;
        average_bp: string | null;
        latest_glucose: string | null;
        latest_glucose_range_label: string | null;
        average_glucose: string | null;
        days_with_bp: number;
        days_with_three_bp: number;
        glucose_unit: string;
    };
    bp_trend: { date: string; label: string; systolic: number; diastolic: number }[];
    glucose_trend: { date: string; label: string; value: number }[];
    bp_slots: {
        key: string;
        label: string;
        count: number;
        average_systolic: number | null;
        average_diastolic: number | null;
    }[];
    summary_text: string;
};

const props = defineProps<{
    insights: Insights;
    filters: Filters;
    presets: { key: string; label: string; from: string; to: string }[];
    month: {
        label: string;
        prev_label: string;
        next_label: string;
        prev_from: string;
        prev_to: string;
        next_from: string;
        next_to: string;
        can_next: boolean;
    };
}>();

const showBpChart = computed(() => props.insights.bp_trend.length >= 2);
const showSugarChart = computed(() => props.insights.glucose_trend.length >= 2);
const showSlotChart = computed(() =>
    props.insights.bp_slots.some((slot) => slot.count > 0),
);

const empty = computed(
    () =>
        props.insights.summary.bp_count === 0 &&
        props.insights.summary.glucose_count === 0,
);

const chartsReady = ref(false);

onMounted(() => {
    chartsReady.value = true;
});
</script>

<template>
    <Head title="Trends" />

    <div class="flex flex-1 flex-col gap-4">
        <p
            class="text-muted-foreground text-base leading-relaxed"
            data-test="trends-summary"
        >
            {{ insights.summary_text }}
        </p>

        <HistoryDateRange
            :filters="filters"
            :presets="presets"
            :month="month"
            :url-for="(from, to) => trends.url({ query: { from, to } })"
        />

        <Link
            :href="
                report.url({
                    query: { from: filters.from, to: filters.to },
                })
            "
            class="bg-card inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-full border text-base font-medium shadow-sm"
            data-test="print-for-doctor"
        >
            <Printer class="size-4" aria-hidden="true" />
            Print for doctor
        </Link>

        <div v-if="empty" class="hl-card px-4 py-6">
            <p class="text-base leading-relaxed">
                Log a few blood pressure or sugar readings to see charts here.
            </p>
            <div class="mt-4 grid gap-2">
                <Link
                    :href="logBp()"
                    class="bg-primary text-primary-foreground inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-xl text-base font-medium"
                >
                    <Activity class="size-5" aria-hidden="true" />
                    Log blood pressure
                </Link>
                <Link
                    :href="logSugar()"
                    class="inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-xl border text-base font-medium"
                >
                    <Droplet class="size-5" aria-hidden="true" />
                    Log sugar
                </Link>
            </div>
        </div>

        <template v-else>
            <section class="grid grid-cols-2 gap-3">
                <div class="hl-card px-3 py-3">
                    <p class="text-muted-foreground text-sm">Average BP</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums" data-test="avg-bp">
                        {{ insights.summary.average_bp ?? '—' }}
                    </p>
                </div>
                <div class="hl-card px-3 py-3">
                    <p class="text-muted-foreground text-sm">Latest BP</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">
                        {{ insights.summary.latest_bp ?? '—' }}
                    </p>
                    <p
                        v-if="insights.summary.latest_bp_range_label"
                        class="mt-1 text-sm font-medium"
                    >
                        {{ insights.summary.latest_bp_range_label }}
                    </p>
                </div>
                <div class="hl-card px-3 py-3">
                    <p class="text-muted-foreground text-sm">Average sugar</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums" data-test="avg-sugar">
                        {{ insights.summary.average_glucose ?? '—' }}
                    </p>
                </div>
                <div class="hl-card px-3 py-3">
                    <p class="text-muted-foreground text-sm">3-time days</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums" data-test="bp-complete-days">
                        {{ insights.summary.days_with_three_bp }}
                        <span class="text-muted-foreground text-base font-normal">
                            of {{ insights.summary.days_with_bp }}
                        </span>
                    </p>
                </div>
            </section>

            <section class="hl-card px-4 py-4">
                <h2 class="flex items-center gap-2 text-lg font-semibold">
                    <Activity class="size-5" aria-hidden="true" />
                    Blood pressure
                </h2>
                <p class="text-muted-foreground mt-1 text-base">
                    Daily average. Solid line is systolic, dashed is diastolic.
                </p>
                <p
                    v-if="!showBpChart"
                    class="text-muted-foreground mt-4 text-base"
                >
                    Need readings on at least two days to draw this chart.
                </p>
                <TrendLineChart
                    v-else-if="chartsReady"
                    class="mt-3"
                    y-label="mmHg"
                    :labels="insights.bp_trend.map((day) => day.label)"
                    :series="[
                        {
                            label: 'Systolic (top number)',
                            values: insights.bp_trend.map((day) => day.systolic),
                            color: '#0369a1',
                        },
                        {
                            label: 'Diastolic (bottom number)',
                            values: insights.bp_trend.map((day) => day.diastolic),
                            dashed: true,
                            color: '#0f766e',
                        },
                    ]"
                />
                <details v-if="showBpChart" class="mt-3">
                    <summary class="min-h-11 cursor-pointer text-base font-medium">
                        View blood pressure numbers
                    </summary>
                    <table class="mt-2 w-full text-left text-base">
                        <caption class="sr-only">
                            Daily average blood pressure
                        </caption>
                        <thead>
                            <tr class="text-muted-foreground">
                                <th scope="col" class="py-2 pr-2">Day</th>
                                <th scope="col" class="py-2 pr-2">Systolic</th>
                                <th scope="col" class="py-2">Diastolic</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="day in insights.bp_trend"
                                :key="day.date"
                                class="border-t tabular-nums"
                            >
                                <td class="py-2 pr-2">{{ day.label }}</td>
                                <td class="py-2 pr-2">{{ day.systolic }}</td>
                                <td class="py-2">{{ day.diastolic }}</td>
                            </tr>
                        </tbody>
                    </table>
                </details>
            </section>

            <section class="hl-card px-4 py-4">
                <h2 class="text-lg font-semibold">Morning, afternoon, evening</h2>
                <p class="text-muted-foreground mt-1 text-base">
                    How many BP readings you logged in each part of the day.
                </p>
                <p
                    v-if="!showSlotChart"
                    class="text-muted-foreground mt-4 text-base"
                >
                    No blood pressure readings in this range.
                </p>
                <TrendBarChart
                    v-else-if="chartsReady"
                    class="mt-3"
                    y-label="Readings"
                    :labels="insights.bp_slots.map((slot) => slot.label)"
                    :series="[
                        {
                            label: 'Readings logged',
                            values: insights.bp_slots.map((slot) => slot.count),
                            color: '#0369a1',
                        },
                    ]"
                />
                <ul class="mt-3 grid gap-1 text-base">
                    <li
                        v-for="slot in insights.bp_slots"
                        :key="slot.key"
                        class="flex justify-between gap-3"
                    >
                        <span>{{ slot.label }}</span>
                        <span class="tabular-nums">
                            {{ slot.count }}
                            <template v-if="slot.average_systolic !== null">
                                · avg {{ slot.average_systolic }} /
                                {{ slot.average_diastolic }}
                            </template>
                        </span>
                    </li>
                </ul>
            </section>

            <section class="hl-card px-4 py-4">
                <h2 class="flex items-center gap-2 text-lg font-semibold">
                    <Droplet class="size-5" aria-hidden="true" />
                    Sugar
                </h2>
                <p class="text-muted-foreground mt-1 text-base">
                    Daily average in {{ insights.summary.glucose_unit }}.
                </p>
                <p
                    v-if="!showSugarChart"
                    class="text-muted-foreground mt-4 text-base"
                >
                    Need sugar readings on at least two days to draw this chart.
                </p>
                <TrendLineChart
                    v-else-if="chartsReady"
                    class="mt-3"
                    :y-label="insights.summary.glucose_unit"
                    :labels="insights.glucose_trend.map((day) => day.label)"
                    :series="[
                        {
                            label: `Sugar (${insights.summary.glucose_unit})`,
                            values: insights.glucose_trend.map((day) => day.value),
                            color: '#047857',
                        },
                    ]"
                />
                <details v-if="showSugarChart" class="mt-3">
                    <summary class="min-h-11 cursor-pointer text-base font-medium">
                        View sugar numbers
                    </summary>
                    <table class="mt-2 w-full text-left text-base">
                        <caption class="sr-only">Daily average sugar</caption>
                        <thead>
                            <tr class="text-muted-foreground">
                                <th scope="col" class="py-2 pr-2">Day</th>
                                <th scope="col" class="py-2">
                                    {{ insights.summary.glucose_unit }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="day in insights.glucose_trend"
                                :key="day.date"
                                class="border-t tabular-nums"
                            >
                                <td class="py-2 pr-2">{{ day.label }}</td>
                                <td class="py-2">{{ day.value }}</td>
                            </tr>
                        </tbody>
                    </table>
                </details>
            </section>
        </template>

        <p class="text-muted-foreground text-sm leading-relaxed">
            These charts are a record of what you logged. They are not a
            diagnosis or medical advice.
        </p>
    </div>
</template>
