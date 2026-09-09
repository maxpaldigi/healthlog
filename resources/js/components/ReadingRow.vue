<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type TimelineItem = {
    id: number;
    type: 'bp' | 'glucose';
    title: string;
    subtitle: string;
    context_label: string;
    color: string;
    range_label?: string | null;
    note?: string | null;
    measured_at_label: string;
    edit_url: string;
};

defineProps<{
    entry: TimelineItem;
    testId?: string;
}>();

const barClass: Record<string, string> = {
    crisis: 'bg-red-600',
    high: 'bg-orange-500',
    raised: 'bg-amber-500',
    elevated: 'bg-yellow-400',
    normal: 'bg-emerald-600',
    glucose: 'bg-primary',
};

const typeLabel: Record<string, string> = {
    bp: 'Blood pressure',
    glucose: 'Sugar',
};
</script>

<template>
    <Link
        :href="entry.edit_url"
        :class="[
            'hl-card flex min-h-16 cursor-pointer items-stretch gap-3 px-3 py-3 transition-transform duration-200',
            'active:scale-[0.99]',
        ]"
        :data-test="testId"
    >
        <span
            class="my-0.5 w-1.5 shrink-0 rounded-full"
            :class="barClass[entry.color] ?? 'bg-border'"
            aria-hidden="true"
        />
        <div class="min-w-0 flex-1">
            <p class="text-xl font-semibold tabular-nums">
                {{ entry.title }}
                <span
                    v-if="entry.range_label"
                    class="ml-2 text-base font-medium"
                >
                    {{ entry.range_label }}
                </span>
            </p>
            <p class="text-muted-foreground mt-0.5 text-base">
                {{ typeLabel[entry.type] }}
                <span aria-hidden="true"> · </span>
                {{ entry.context_label }}
            </p>
            <p v-if="entry.note" class="text-muted-foreground mt-0.5 text-sm">
                {{ entry.note }}
            </p>
        </div>
        <p class="text-muted-foreground shrink-0 self-center text-sm">
            {{ entry.measured_at_label }}
        </p>
    </Link>
</template>
