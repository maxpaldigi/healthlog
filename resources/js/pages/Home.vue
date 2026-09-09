<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Activity, Camera, ChevronRight, Droplet, Moon, Plus, Sun, Sunrise } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useSlotReminders } from '@/composables/useSlotReminders';
import ReadingRow from '@/components/ReadingRow.vue';
import TodayGauge from '@/components/TodayGauge.vue';
import { create as logBp } from '@/routes/bp';
import { create as logSugar } from '@/routes/glucose';
import { home, scan } from '@/routes';
import { current as switchProfile } from '@/routes/profiles';

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

const props = defineProps<{
    profile: { id: number; name: string };
    profiles: { id: number; name: string }[];
    todayCount: number;
    todayTarget: number;
    todaySlots: { key: string; label: string; filled: boolean }[];
    recent: TimelineItem[];
    reminders?: {
        enabled: boolean;
        timezone: string;
        times: { morning: string; afternoon: string; evening: string };
        due: { key: string; label: string; time: string }[];
    };
}>();

const reminders = computed(
    () =>
        props.reminders ?? {
            enabled: false,
            timezone: 'UTC',
            times: { morning: '08:00', afternoon: '14:00', evening: '20:00' },
            due: [],
        },
);

const firstName = computed(
    () => props.profile.name.trim().split(/\s+/)[0] || 'there',
);

const dateLabel = computed(() =>
    new Date().toLocaleDateString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    }),
);

const todayStatusText = computed(() => {
    if (props.todayCount >= props.todayTarget) {
        return `${props.todayCount} blood pressure readings logged today`;
    }

    return `${props.todayCount} of ${props.todayTarget} blood pressure readings today`;
});

const { permission, enableBrowser } = useSlotReminders(reminders.value);

const slotIcons: Record<string, Component> = {
    morning: Sunrise,
    afternoon: Sun,
    evening: Moon,
};
</script>

<template>
    <Head title="Home" />

    <div class="flex flex-1 flex-col gap-6">
        <header class="space-y-1">
            <h1 class="text-3xl font-bold">Hello, {{ firstName }}</h1>
            <p class="text-muted-foreground text-lg">
                Photograph the meter, or type the numbers.
            </p>
            <Link
                :href="home.url({ query: { guide: '1' } })"
                class="text-primary inline-flex min-h-11 items-center text-base underline-offset-4 hover:underline"
                data-test="open-guide"
            >
                How to use this app
            </Link>
        </header>

        <div v-if="profiles.length > 1" class="flex flex-wrap gap-2">
            <Form
                v-for="item in profiles"
                :key="item.id"
                v-bind="switchProfile.form({ profile: item.id })"
            >
                <Button
                    type="submit"
                    class="h-12 min-w-12 cursor-pointer px-4 text-base"
                    :variant="item.id === profile.id ? 'default' : 'outline'"
                >
                    {{ item.name }}
                </Button>
            </Form>
        </div>

        <section
            v-if="reminders.enabled && reminders.due.length > 0"
            class="rounded-[1.5rem] border border-amber-500 bg-amber-50 px-4 py-4 text-amber-950"
            data-test="reminder-due"
        >
            <p class="text-lg font-semibold">Time to log</p>
            <p class="mt-1 text-base leading-relaxed">
                {{ reminders.due.map((slot) => slot.label).join(', ') }}
                blood pressure is still open.
            </p>
            <Link
                :href="logBp()"
                class="mt-3 inline-flex min-h-12 items-center justify-center rounded-2xl bg-amber-900 px-4 text-base font-medium text-white"
            >
                Add blood pressure
            </Link>
            <button
                v-if="permission === 'default'"
                type="button"
                class="mt-2 block min-h-11 text-base underline"
                @click="enableBrowser"
            >
                Also notify me in this browser
            </button>
        </section>

        <section
            class="hl-card px-5 pb-5 pt-6"
            aria-live="polite"
            data-tour="today"
        >
            <TodayGauge
                :count="todayCount"
                :target="todayTarget"
                :date-label="dateLabel"
                :status-text="todayStatusText"
            />
            <div class="mt-2 grid grid-cols-3 gap-2">
                <div
                    v-for="slot in todaySlots"
                    :key="slot.key"
                    class="flex min-h-14 flex-col items-center justify-center rounded-2xl px-1 py-2 text-center"
                    :class="
                        slot.filled
                            ? 'bg-accent text-accent-foreground'
                            : 'bg-muted text-muted-foreground'
                    "
                >
                    <component
                        :is="slotIcons[slot.key]"
                        class="size-5"
                        aria-hidden="true"
                    />
                    <span class="mt-1 text-sm font-medium">{{ slot.label }}</span>
                    <span class="sr-only">
                        {{ slot.filled ? 'logged' : 'not yet' }}
                    </span>
                </div>
            </div>
        </section>

        <Link
            :href="scan()"
            class="bg-nav text-nav-foreground flex min-h-16 cursor-pointer items-center justify-center gap-3 rounded-full px-5 text-xl font-bold shadow-[0_10px_24px_-10px_rgb(28_25_23_/_0.45)] transition-transform duration-200 active:scale-[0.98]"
            data-test="scan"
            data-tour="scan-photo"
        >
            <span
                class="bg-cta text-cta-foreground flex size-11 items-center justify-center rounded-full"
                aria-hidden="true"
            >
                <Plus class="size-6" />
            </span>
            Take a photo
            <Camera class="size-6" aria-hidden="true" />
        </Link>
        <p class="text-muted-foreground -mt-3 text-center text-base">
            Point the camera at a BP monitor or sugar meter.
        </p>

        <section class="space-y-3" data-tour="log-manual">
            <div class="space-y-1">
                <h2 class="text-xl font-bold">Add a reading</h2>
                <p class="text-muted-foreground text-base leading-relaxed">
                    Choose what you measured. Tap a card to type the numbers.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <Link
                    :href="logBp()"
                    class="hl-card flex min-h-44 cursor-pointer flex-col items-start gap-2 border-t-4 border-t-primary px-3 py-4 transition-transform duration-200 active:scale-[0.98] sm:px-4"
                    data-test="log-bp"
                >
                    <span
                        class="bg-primary text-primary-foreground flex size-12 items-center justify-center rounded-full"
                        aria-hidden="true"
                    >
                        <Activity class="size-6" />
                    </span>
                    <span class="text-lg font-bold leading-tight">
                        Add blood pressure
                    </span>
                    <span class="text-muted-foreground text-base leading-relaxed">
                        Arm cuff
                    </span>
                    <span class="text-base font-medium tabular-nums">
                        132 / 84
                    </span>
                    <span
                        class="bg-primary text-primary-foreground mt-auto inline-flex min-h-11 w-full items-center justify-center gap-1 rounded-full text-base font-semibold"
                    >
                        Add
                        <ChevronRight class="size-5" aria-hidden="true" />
                    </span>
                </Link>

                <Link
                    :href="logSugar()"
                    class="hl-card flex min-h-44 cursor-pointer flex-col items-start gap-2 border-t-4 border-t-success px-3 py-4 transition-transform duration-200 active:scale-[0.98] sm:px-4"
                    data-test="log-sugar"
                >
                    <span
                        class="bg-success text-success-foreground flex size-12 items-center justify-center rounded-full"
                        aria-hidden="true"
                    >
                        <Droplet class="size-6" />
                    </span>
                    <span class="text-lg font-bold leading-tight">
                        Add blood sugar
                    </span>
                    <span class="text-muted-foreground text-base leading-relaxed">
                        Sugar meter
                    </span>
                    <span class="text-base font-medium tabular-nums">
                        110
                    </span>
                    <span
                        class="bg-success text-success-foreground mt-auto inline-flex min-h-11 w-full items-center justify-center gap-1 rounded-full text-base font-semibold"
                    >
                        Add
                        <ChevronRight class="size-5" aria-hidden="true" />
                    </span>
                </Link>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-bold">Last readings</h2>
            <p
                v-if="recent.length === 0"
                class="text-muted-foreground text-base leading-relaxed"
            >
                Nothing here yet. Take a photo or type a reading above.
            </p>
            <ReadingRow
                v-for="entry in recent"
                :key="`${entry.type}-${entry.id}`"
                :entry="entry"
            />
        </section>

        <p class="text-muted-foreground mt-auto pt-2 text-sm leading-relaxed">
            This app is a record-keeping tool, not a medical device, and does
            not provide medical advice. If a reading is 180/120 or higher, or
            if you feel unwell, seek immediate medical attention.
        </p>
    </div>
</template>
