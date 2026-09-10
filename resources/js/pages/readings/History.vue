<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Activity, Droplet, Printer, Rows3 } from '@lucide/vue';
import { computed } from 'vue';
import HistoryDateRange from '@/components/HistoryDateRange.vue';
import HistoryDayTable from '@/components/HistoryDayTable.vue';
import type { TimelineItem } from '@/components/HistoryDayTable.vue';
import { history, report } from '@/routes';

type DayGroup = {
    date_key: string;
    date_label: string;
    bpFilled: number;
    bpSlots: Record<'morning' | 'afternoon' | 'evening', TimelineItem[]>;
    sugar: TimelineItem[];
};

const props = defineProps<{
    entries: TimelineItem[];
    filters: {
        type: string;
        from: string;
        to: string;
        from_label: string;
        to_label: string;
        range_label: string;
        today: string;
        earliest: string;
        preset: string | null;
    };
    presets: {
        key: string;
        label: string;
        from: string;
        to: string;
    }[];
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

const days = computed((): DayGroup[] => {
    const groups = new Map<string, DayGroup>();

    for (const entry of props.entries) {
        let day = groups.get(entry.date_key);

        if (!day) {
            day = {
                date_key: entry.date_key,
                date_label: entry.date_label,
                bpFilled: 0,
                bpSlots: { morning: [], afternoon: [], evening: [] },
                sugar: [],
            };
            groups.set(entry.date_key, day);
        }

        if (entry.type === 'bp' && entry.slot) {
            day.bpSlots[entry.slot].push(entry);
        } else if (entry.type === 'glucose') {
            day.sugar.push(entry);
        }
    }

    return [...groups.values()].map((day) => ({
        ...day,
        bpFilled: (
            ['morning', 'afternoon', 'evening'] as const
        ).filter((slot) => day.bpSlots[slot].length > 0).length,
    }));
});

const showBp = computed(
    () => props.filters.type === 'all' || props.filters.type === 'bp',
);
const showSugar = computed(
    () => props.filters.type === 'all' || props.filters.type === 'glucose',
);
</script>

<template>
    <Head title="History" />

    <div class="flex flex-1 flex-col gap-4">
        <div class="grid grid-cols-3 gap-2">
            <Link
                v-for="option in [
                    { value: 'all', label: 'All', icon: Rows3 },
                    { value: 'bp', label: 'BP', icon: Activity },
                    { value: 'glucose', label: 'Sugar', icon: Droplet },
                ]"
                :key="option.value"
                :href="
                    history.url({
                        query: {
                            type: option.value,
                            from: filters.from,
                            to: filters.to,
                        },
                    })
                "
                class="inline-flex min-h-14 cursor-pointer items-center justify-center gap-1.5 rounded-full border text-base font-medium"
                :class="
                    filters.type === option.value
                        ? 'bg-primary text-primary-foreground border-primary'
                        : 'bg-card text-foreground'
                "
            >
                <component
                    v-if="option.icon"
                    :is="option.icon"
                    class="size-4"
                    aria-hidden="true"
                />
                {{ option.label }}
            </Link>
        </div>

        <p
            v-if="showBp"
            class="text-muted-foreground text-base leading-relaxed"
        >
            Doctors often want blood pressure 3 times a day: morning, afternoon,
            and evening.
        </p>

        <HistoryDateRange
            :filters="filters"
            :presets="presets"
            :month="month"
            :url-for="
                (from, to) =>
                    history.url({
                        query: {
                            type: filters.type,
                            from,
                            to,
                        },
                    })
            "
        />

        <Link
            :href="
                report.url({
                    query: { from: filters.from, to: filters.to },
                })
            "
            class="bg-card inline-flex min-h-14 cursor-pointer items-center justify-center gap-2 rounded-full border text-base font-medium shadow-sm"
            data-test="print-for-doctor"
        >
            <Printer class="size-4" aria-hidden="true" />
            Print for doctor
        </Link>

        <p v-if="entries.length === 0" class="text-muted-foreground text-base">
            No readings in this range.
        </p>

        <div class="grid gap-4">
            <HistoryDayTable
                v-for="day in days"
                :key="day.date_key"
                :day="day"
                :show-bp="showBp && (filters.type === 'bp' || day.bpFilled > 0)"
                :show-sugar="showSugar && day.sugar.length > 0"
            />
        </div>
    </div>
</template>
