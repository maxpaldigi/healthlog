<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft, ChevronRight } from '@lucide/vue';

type Preset = {
    key: string;
    label: string;
    from: string;
    to: string;
};

const props = defineProps<{
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
        type?: string;
    };
    presets: Preset[];
    urlFor: (from: string, to: string) => string;
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

const rangeUrl = (from: string, to: string): string => props.urlFor(from, to);

const applyDates = (from: string, to: string): void => {
    let nextFrom = from;
    let nextTo = to;

    if (nextFrom > nextTo) {
        [nextFrom, nextTo] = [nextTo, nextFrom];
    }

    if (nextFrom === props.filters.from && nextTo === props.filters.to) {
        return;
    }

    router.get(
        rangeUrl(nextFrom, nextTo),
        {},
        { preserveScroll: true },
    );
};

const onStart = (event: Event): void => {
    const value = (event.target as HTMLInputElement).value;
    if (value) {
        applyDates(value, props.filters.to);
    }
};

const onEnd = (event: Event): void => {
    const value = (event.target as HTMLInputElement).value;
    if (value) {
        applyDates(props.filters.from, value);
    }
};
</script>

<template>
    <section class="hl-card px-4 py-4">
        <div class="flex items-start gap-3">
            <span
                class="bg-primary/10 text-primary mt-0.5 flex size-11 shrink-0 items-center justify-center rounded-xl"
                aria-hidden="true"
            >
                <CalendarDays class="size-6" />
            </span>
            <div class="min-w-0">
                <p class="text-muted-foreground text-base">Showing</p>
                <p class="text-xl font-semibold leading-tight" data-test="date-range-label">
                    {{ filters.range_label }}
                </p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-3 gap-2">
            <Link
                v-for="preset in presets"
                :key="preset.key"
                :href="rangeUrl(preset.from, preset.to)"
                preserve-scroll
                class="inline-flex min-h-12 cursor-pointer items-center justify-center rounded-xl border px-2 text-center text-base font-medium"
                :class="
                    filters.preset === preset.key
                        ? 'bg-primary text-primary-foreground border-primary'
                        : 'bg-background text-foreground'
                "
                :data-test="`date-preset-${preset.key}`"
            >
                {{ preset.label }}
            </Link>
        </div>

        <div class="mt-3 grid grid-cols-[1fr_auto_1fr] items-center gap-2">
            <Link
                :href="rangeUrl(month.prev_from, month.prev_to)"
                preserve-scroll
                class="inline-flex min-h-12 cursor-pointer items-center justify-center gap-1 rounded-xl border bg-background px-2 text-base font-medium"
                :aria-label="`Earlier month, ${month.prev_label}`"
                data-test="date-prev-month"
            >
                <ChevronLeft class="size-5 shrink-0" aria-hidden="true" />
                {{ month.prev_label }}
            </Link>
            <p class="px-1 text-center text-base font-semibold" data-test="date-month-label">
                {{ month.label }}
            </p>
            <Link
                v-if="month.can_next"
                :href="rangeUrl(month.next_from, month.next_to)"
                preserve-scroll
                class="inline-flex min-h-12 cursor-pointer items-center justify-center gap-1 rounded-xl border bg-background px-2 text-base font-medium"
                :aria-label="`Later month, ${month.next_label}`"
                data-test="date-next-month"
            >
                {{ month.next_label }}
                <ChevronRight class="size-5 shrink-0" aria-hidden="true" />
            </Link>
            <span
                v-else
                class="text-muted-foreground inline-flex min-h-12 items-center justify-center gap-1 rounded-xl border border-transparent px-2 text-base"
                aria-disabled="true"
            >
                {{ month.next_label }}
                <ChevronRight class="size-5 shrink-0" aria-hidden="true" />
            </span>
        </div>

        <p class="text-muted-foreground mt-4 text-base">Or pick start and end</p>
        <div class="mt-2 grid grid-cols-2 gap-3">
            <label class="relative block cursor-pointer">
                <span class="sr-only">Start date</span>
                <span
                    class="bg-background flex min-h-16 flex-col justify-center rounded-xl border px-3 py-2"
                >
                    <span class="text-muted-foreground text-sm">Start</span>
                    <span class="text-lg font-semibold leading-tight">
                        {{ filters.from_label }}
                    </span>
                </span>
                <input
                    id="from"
                    type="date"
                    name="from"
                    :value="filters.from"
                    :min="filters.earliest"
                    :max="filters.today"
                    class="absolute inset-0 cursor-pointer opacity-0"
                    data-test="date-from"
                    @change="onStart"
                />
            </label>
            <label class="relative block cursor-pointer">
                <span class="sr-only">End date</span>
                <span
                    class="bg-background flex min-h-16 flex-col justify-center rounded-xl border px-3 py-2"
                >
                    <span class="text-muted-foreground text-sm">End</span>
                    <span class="text-lg font-semibold leading-tight">
                        {{ filters.to_label }}
                    </span>
                </span>
                <input
                    id="to"
                    type="date"
                    name="to"
                    :value="filters.to"
                    :min="filters.earliest"
                    :max="filters.today"
                    class="absolute inset-0 cursor-pointer opacity-0"
                    data-test="date-to"
                    @change="onEnd"
                />
            </label>
        </div>
    </section>
</template>
