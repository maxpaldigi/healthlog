<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Activity, Camera, Droplet, Moon, Sun, Sunrise } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useSlotReminders } from '@/composables/useSlotReminders';
import ReadingRow from '@/components/ReadingRow.vue';
import { create as logBp } from '@/routes/bp';
import { create as logSugar } from '@/routes/glucose';
import { scan } from '@/routes';
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
                Log a reading when you have a minute.
            </p>
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
            class="rounded-2xl border border-amber-400 bg-amber-50 px-4 py-3 text-amber-950"
            data-test="reminder-due"
        >
            <p class="text-lg font-semibold">Time to log</p>
            <p class="mt-1 text-base leading-relaxed">
                {{ reminders.due.map((slot) => slot.label).join(', ') }}
                blood pressure is still open.
            </p>
            <Link
                :href="logBp()"
                class="mt-3 inline-flex min-h-12 items-center justify-center rounded-xl bg-amber-900 px-4 text-base font-medium text-white"
            >
                Log blood pressure
            </Link>
            <button
                v-if="permission === 'default'"
                type="button"
                class="mt-2 block text-base underline"
                @click="enableBrowser"
            >
                Also notify me in this browser
            </button>
        </section>

        <section class="hl-card px-5 py-4" aria-live="polite">
            <p class="text-muted-foreground text-base">Today</p>
            <p class="mt-1 text-xl font-bold" data-test="today-status">
                <template v-if="todayCount >= todayTarget">
                    {{ todayCount }} BP readings logged
                </template>
                <template v-else>
                    {{ todayCount }} of {{ todayTarget }} BP readings
                </template>
            </p>
            <p class="text-muted-foreground mt-1 text-base">
                Morning, afternoon, and evening.
            </p>
            <div class="mt-3 grid grid-cols-3 gap-2">
                <div
                    v-for="slot in todaySlots"
                    :key="slot.key"
                    class="flex min-h-12 flex-col items-center justify-center rounded-xl border px-1 py-2 text-center"
                    :class="
                        slot.filled
                            ? 'border-success bg-accent text-accent-foreground'
                            : 'border-border bg-background text-muted-foreground'
                    "
                >
                    <component
                        :is="slotIcons[slot.key]"
                        class="size-4"
                        aria-hidden="true"
                    />
                    <span class="mt-1 text-sm font-medium">{{ slot.label }}</span>
                    <span class="sr-only">
                        {{ slot.filled ? 'logged' : 'not yet' }}
                    </span>
                </div>
            </div>
        </section>

        <div class="grid gap-3">
            <Link
                :href="logBp()"
                class="hl-card flex min-h-24 cursor-pointer items-center gap-4 px-4 py-4 transition-transform duration-200 active:scale-[0.98]"
                data-test="log-bp"
            >
                <span
                    class="bg-primary text-primary-foreground flex size-14 shrink-0 items-center justify-center rounded-2xl"
                >
                    <Activity class="size-7" aria-hidden="true" />
                </span>
                <span class="min-w-0">
                    <span class="block text-xl font-bold">Log blood pressure</span>
                    <span class="text-muted-foreground mt-0.5 block text-base">
                        3 times a day: morning, afternoon, evening
                    </span>
                </span>
            </Link>

            <Link
                :href="logSugar()"
                class="hl-card flex min-h-24 cursor-pointer items-center gap-4 px-4 py-4 transition-transform duration-200 active:scale-[0.98]"
                data-test="log-sugar"
            >
                <span
                    class="bg-success text-success-foreground flex size-14 shrink-0 items-center justify-center rounded-2xl"
                >
                    <Droplet class="size-7" aria-hidden="true" />
                </span>
                <span class="min-w-0">
                    <span class="block text-xl font-bold">Log sugar</span>
                    <span class="text-muted-foreground mt-0.5 block text-base">
                        Type the number from the meter
                    </span>
                </span>
            </Link>

            <Link
                :href="scan.url({ query: { expect: 'glucose' } })"
                class="hl-card flex min-h-24 cursor-pointer items-center gap-4 px-4 py-4 transition-transform duration-200 active:scale-[0.98]"
                data-test="scan-sugar"
            >
                <span
                    class="bg-success/15 text-success flex size-14 shrink-0 items-center justify-center rounded-2xl"
                >
                    <Camera class="size-7" aria-hidden="true" />
                </span>
                <span class="min-w-0">
                    <span class="block text-xl font-bold">Photo of sugar meter</span>
                    <span class="text-muted-foreground mt-0.5 block text-base">
                        Take a picture, then check the number
                    </span>
                </span>
            </Link>

            <Link
                :href="scan()"
                class="text-foreground flex min-h-14 cursor-pointer items-center justify-center gap-2 text-lg font-medium underline-offset-4 hover:underline"
                data-test="scan"
            >
                <Camera class="size-5" aria-hidden="true" />
                Scan a blood pressure photo
            </Link>
        </div>

        <section class="space-y-3">
            <h2 class="text-xl font-bold">Last readings</h2>
            <p
                v-if="recent.length === 0"
                class="text-muted-foreground text-base leading-relaxed"
            >
                Nothing here yet. Start with blood pressure or sugar above.
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
