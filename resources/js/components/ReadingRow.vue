<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Activity, Droplet, Pencil } from '@lucide/vue';

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

const iconWrap: Record<string, string> = {
    crisis: 'bg-[var(--bp-crisis)] text-[var(--bp-crisis-fg)]',
    high: 'bg-[var(--bp-high)] text-[var(--bp-high-fg)]',
    raised: 'bg-[var(--bp-raised)] text-[var(--bp-raised-fg)]',
    elevated: 'bg-[var(--bp-elevated)] text-[var(--bp-elevated-fg)]',
    normal: 'bg-[var(--bp-normal)] text-[var(--bp-normal-fg)]',
    glucose: 'bg-primary text-primary-foreground',
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
            'hl-card flex min-h-16 cursor-pointer items-center gap-3 px-3 py-3 transition-transform duration-200',
            'active:scale-[0.99]',
        ]"
        :aria-label="`Edit ${typeLabel[entry.type]} ${entry.title}`"
        :data-test="testId"
    >
        <span
            class="flex size-12 shrink-0 items-center justify-center rounded-full"
            :class="iconWrap[entry.color] ?? 'bg-muted text-foreground'"
            aria-hidden="true"
        >
            <Activity v-if="entry.type === 'bp'" class="size-6" />
            <Droplet v-else class="size-6" />
        </span>
        <div class="min-w-0 flex-1">
            <p class="text-lg font-semibold leading-tight">
                {{ typeLabel[entry.type] }}
            </p>
            <p class="text-muted-foreground mt-0.5 text-base">
                {{ entry.context_label }}
                <span aria-hidden="true"> · </span>
                {{ entry.measured_at_label }}
            </p>
            <p class="mt-1 text-xl font-bold tabular-nums">
                {{ entry.title }}
                <span
                    v-if="entry.range_label"
                    class="ml-2 text-base font-medium"
                >
                    {{ entry.range_label }}
                </span>
            </p>
            <p v-if="entry.note" class="text-muted-foreground mt-0.5 text-sm">
                {{ entry.note }}
            </p>
        </div>
        <span
            class="text-muted-foreground flex size-11 shrink-0 items-center justify-center rounded-full"
            aria-hidden="true"
        >
            <Pencil class="size-5" />
        </span>
        <span class="sr-only">Edit this reading</span>
    </Link>
</template>
