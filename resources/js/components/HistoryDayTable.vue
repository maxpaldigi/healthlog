<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Droplet, Moon, Sun, Sunrise } from '@lucide/vue';
import type { Component } from 'vue';

export type TimelineItem = {
    id: number;
    type: 'bp' | 'glucose';
    title: string;
    subtitle: string;
    context_label: string;
    color: string;
    measured_at_label: string;
    date_key: string;
    date_label: string;
    time_label: string;
    slot: 'morning' | 'afternoon' | 'evening' | null;
    slot_label: string | null;
    range_label?: string | null;
    note?: string | null;
    edit_url: string;
};

type DayGroup = {
    date_key: string;
    date_label: string;
    bpFilled: number;
    bpSlots: Record<'morning' | 'afternoon' | 'evening', TimelineItem[]>;
    sugar: TimelineItem[];
};

defineProps<{
    day: DayGroup;
    showBp: boolean;
    showSugar: boolean;
}>();

const slotIcons: Record<string, Component> = {
    morning: Sunrise,
    afternoon: Sun,
    evening: Moon,
};

const slotOrder = ['morning', 'afternoon', 'evening'] as const;

const colorDot: Record<string, string> = {
    crisis: 'bg-red-600',
    high: 'bg-orange-500',
    raised: 'bg-amber-500',
    elevated: 'bg-yellow-400',
    normal: 'bg-emerald-600',
    glucose: 'bg-primary',
};
</script>

<template>
    <section class="hl-card overflow-hidden">
        <header
            class="flex items-center justify-between gap-3 border-b px-3 py-3"
        >
            <h2 class="text-lg font-semibold">{{ day.date_label }}</h2>
            <p
                v-if="showBp"
                class="text-muted-foreground text-sm"
            >
                BP {{ day.bpFilled }} of 3
            </p>
        </header>

        <div v-if="showBp" class="overflow-x-auto">
            <table class="w-full min-w-[20rem] table-fixed text-left">
                <caption class="sr-only">
                    Blood pressure for {{ day.date_label }}.
                    {{ day.bpFilled }} of 3 times logged.
                </caption>
                <thead>
                    <tr class="bg-muted/70 text-muted-foreground">
                        <th
                            v-for="slot in slotOrder"
                            :key="slot"
                            scope="col"
                            class="px-2 py-2 text-center text-sm font-medium"
                        >
                            <span class="inline-flex items-center gap-1">
                                <component
                                    :is="slotIcons[slot]"
                                    class="size-4"
                                    aria-hidden="true"
                                />
                                {{
                                    slot === 'morning'
                                        ? 'Morning'
                                        : slot === 'afternoon'
                                          ? 'Afternoon'
                                          : 'Evening'
                                }}
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="odd:bg-card even:bg-muted/40">
                        <td
                            v-for="slot in slotOrder"
                            :key="slot"
                            class="align-top px-1.5 py-1.5"
                        >
                            <div
                                v-if="day.bpSlots[slot].length === 0"
                                class="text-muted-foreground flex min-h-11 items-center justify-center text-sm"
                            >
                                —
                                <span class="sr-only">No {{ slot }} reading</span>
                            </div>
                            <div v-else class="grid gap-1">
                                <Link
                                    v-for="entry in day.bpSlots[slot]"
                                    :key="entry.id"
                                    :href="entry.edit_url"
                                    class="hover:bg-accent flex min-h-11 cursor-pointer flex-col items-center justify-center rounded-lg px-1 py-1.5 text-center transition-colors duration-200"
                                    :data-test="`history-bp-${entry.id}`"
                                >
                                    <span
                                        class="inline-flex items-center gap-1.5 text-base font-semibold tabular-nums"
                                    >
                                        <span
                                            class="size-2.5 shrink-0 rounded-full"
                                            :class="
                                                colorDot[entry.color] ??
                                                'bg-border'
                                            "
                                            aria-hidden="true"
                                        />
                                        {{ entry.title }}
                                    </span>
                                    <span
                                        v-if="entry.range_label"
                                        class="text-sm font-medium"
                                    >
                                        {{ entry.range_label }}
                                    </span>
                                    <span class="text-muted-foreground text-sm">
                                        {{ entry.time_label }}
                                    </span>
                                    <span
                                        v-if="entry.note"
                                        class="text-muted-foreground line-clamp-2 text-sm"
                                    >
                                        {{ entry.note }}
                                    </span>
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showSugar && day.sugar.length > 0" :class="showBp ? 'border-t' : ''">
            <table class="w-full text-left">
                <caption class="sr-only">
                    Sugar readings for {{ day.date_label }}.
                </caption>
                <thead>
                    <tr class="bg-muted/70 text-muted-foreground">
                        <th scope="col" class="px-3 py-2 text-sm font-medium">
                            <span class="inline-flex items-center gap-1">
                                <Droplet class="size-4" aria-hidden="true" />
                                Sugar
                            </span>
                        </th>
                        <th scope="col" class="px-3 py-2 text-sm font-medium">
                            Time
                        </th>
                        <th scope="col" class="px-3 py-2 text-sm font-medium">
                            When
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="entry in day.sugar"
                        :key="entry.id"
                        class="odd:bg-card even:bg-muted/40"
                    >
                        <td colspan="3" class="p-0">
                            <Link
                                :href="entry.edit_url"
                                class="hover:bg-accent grid min-h-12 cursor-pointer grid-cols-[1fr_auto_auto] items-center gap-3 px-3 py-2 transition-colors duration-200"
                                :data-test="`history-glucose-${entry.id}`"
                            >
                                <span class="inline-flex items-center gap-2 font-semibold tabular-nums">
                                    <span
                                        class="size-2.5 shrink-0 rounded-full bg-primary"
                                        aria-hidden="true"
                                    />
                                    {{ entry.title }}
                                </span>
                                <span class="text-muted-foreground tabular-nums">
                                    {{ entry.time_label }}
                                </span>
                                <span class="text-muted-foreground">
                                    {{ entry.context_label }}
                                    <span v-if="entry.range_label">
                                        · {{ entry.range_label }}
                                    </span>
                                </span>
                            </Link>
                            <p
                                v-if="entry.note"
                                class="text-muted-foreground px-3 pb-2 text-sm"
                            >
                                {{ entry.note }}
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p
            v-if="showBp && !showSugar && day.bpFilled === 0"
            class="text-muted-foreground px-3 py-4 text-base"
        >
            No blood pressure this day.
        </p>

        <p
            v-if="!showBp && showSugar && day.sugar.length === 0"
            class="text-muted-foreground px-3 py-4 text-base"
        >
            No sugar this day.
        </p>
    </section>
</template>
