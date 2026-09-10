<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    Activity,
    Camera,
    ChevronRight,
    Clock,
    Droplet,
    Moon,
    Plus,
    Sun,
    Sunrise,
    Utensils,
    Volume2,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { usePendingReadings } from '@/composables/usePendingReadings';
import { useReadAloud } from '@/composables/useReadAloud';
import { useSlotReminders } from '@/composables/useSlotReminders';
import { useWebPush } from '@/composables/useWebPush';
import ReadingRow from '@/components/ReadingRow.vue';
import TodayGauge from '@/components/TodayGauge.vue';
import { create as logBp } from '@/routes/bp';
import { create as logFood } from '@/routes/food';
import { create as logSugar } from '@/routes/glucose';
import { home, scan, trends } from '@/routes';
import { edit as editHealth } from '@/routes/health-profiles';
import { current as switchProfile } from '@/routes/profiles';

type FoodResponse = {
    after_meals: {
        label: string;
        sample_count: number;
        average: string;
        unit: string;
        line: string;
    }[];
    morning_blood_pressure: {
        label: string;
        sample_count: number;
        average: string;
        line: string;
    }[];
    intro: string;
    hint: string;
};

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
    foodResponse?: FoodResponse;
    role?: string | null;
    access?: {
        is_owner: boolean;
        caregivers: { id: number; email: string; label: string }[];
    };
    alerts?: { key: string; message: string }[];
    summarySpeech?: string;
    reminders?: {
        enabled: boolean;
        timezone: string;
        times: { morning: string; afternoon: string; evening: string };
        due: { key: string; label: string; time: string }[];
    };
    clinicNote?: {
        instruction: string | null;
        acknowledged_on: string;
        targets_line: string | null;
        set_by: string;
    } | null;
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

const access = computed(
    () =>
        props.access ?? {
            is_owner: true,
            caregivers: [],
        },
);

const alerts = computed(() => props.alerts ?? []);

const foodResponse = computed(
    () =>
        props.foodResponse ?? {
            after_meals: [],
            morning_blood_pressure: [],
            intro: 'Your numbers after these foods. Check with your clinic.',
            hint: '',
        },
);

const afterMealsPreview = computed(() =>
    foodResponse.value.after_meals.slice(0, 3),
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
const { subscribe } = useWebPush();
const { items: pending } = usePendingReadings();
const { readAloud, speak } = useReadAloud();

const enableReminders = async (): Promise<void> => {
    await enableBrowser();
    await subscribe();
};

onMounted(() => {
    if (props.summarySpeech) {
        speak(props.summarySpeech);
    }

    if (
        reminders.value.enabled &&
        typeof Notification !== 'undefined' &&
        Notification.permission === 'granted'
    ) {
        void subscribe();
    }
});

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
            <p class="text-muted-foreground text-lg leading-relaxed">
                Photograph a meter, type a number, or add food.
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
                    class="h-14 min-w-14 cursor-pointer px-4 text-base"
                    :variant="item.id === profile.id ? 'default' : 'outline'"
                >
                    {{ item.name }}
                </Button>
            </Form>
        </div>

        <section
            v-if="clinicNote"
            class="border-primary/30 bg-card rounded-[1.5rem] border px-4 py-4"
            data-test="clinic-note"
        >
            <p class="text-lg font-semibold">Note from clinic</p>
            <p class="text-muted-foreground mt-1 text-base leading-relaxed">
                Looked at this log on {{ clinicNote.acknowledged_on }}.
            </p>
            <p
                v-if="clinicNote.instruction"
                class="mt-2 text-base leading-relaxed"
                data-test="clinic-note-text"
            >
                {{ clinicNote.instruction }}
            </p>
            <p
                v-if="clinicNote.targets_line"
                class="mt-2 text-base leading-relaxed"
                data-test="clinic-note-targets"
            >
                {{ clinicNote.targets_line }}
            </p>
        </section>

        <section
            v-if="access.is_owner && access.caregivers.length > 0"
            class="border-primary/30 bg-card rounded-[1.5rem] border px-4 py-4"
            data-test="caregiver-access"
        >
            <p class="text-lg font-semibold">People who can see this log</p>
            <p class="text-muted-foreground mt-1 text-base leading-relaxed">
                {{
                    access.caregivers.map((person) => person.email).join(', ')
                }}. You can remove access in Health profiles.
            </p>
            <Link
                :href="editHealth()"
                class="text-primary mt-2 inline-flex min-h-14 items-center text-base underline-offset-4 hover:underline"
            >
                Manage access
            </Link>
        </section>

        <section
            v-else-if="!access.is_owner"
            class="border-primary/30 bg-card rounded-[1.5rem] border px-4 py-4"
            data-test="helping-banner"
        >
            <p class="text-lg font-semibold">
                You are helping {{ profile.name }}
            </p>
            <p class="text-muted-foreground mt-1 text-base leading-relaxed">
                They can see that you have access, and they can remove it at any
                time.
            </p>
        </section>

        <section
            v-if="alerts.length > 0"
            class="rounded-[1.5rem] border border-amber-500 bg-amber-50 px-4 py-4 text-amber-950"
            data-test="care-alerts"
        >
            <p class="text-lg font-semibold">Attention</p>
            <ul class="mt-2 space-y-1 text-base leading-relaxed">
                <li v-for="alert in alerts" :key="alert.key">
                    {{ alert.message }}
                </li>
            </ul>
        </section>

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
                class="mt-3 inline-flex min-h-14 items-center justify-center rounded-2xl bg-amber-900 px-4 text-base font-medium text-white"
            >
                Add blood pressure
            </Link>
            <button
                v-if="permission === 'default'"
                type="button"
                class="mt-2 block min-h-14 text-base underline"
                @click="enableReminders"
            >
                Also notify me in this browser
            </button>
        </section>

        <section
            v-if="pending.length > 0"
            class="rounded-[1.5rem] border border-amber-500 bg-amber-50 px-4 py-4 text-amber-950"
            data-test="pending-readings"
        >
            <p class="text-lg font-semibold">Waiting to upload</p>
            <p class="mt-1 text-base leading-relaxed">
                These readings are saved on this phone and will upload when you
                are online.
            </p>
            <ul class="mt-3 space-y-2">
                <li
                    v-for="item in pending"
                    :key="item.id"
                    class="flex min-h-14 items-center gap-2 text-base"
                >
                    <Clock class="size-5 shrink-0" aria-hidden="true" />
                    <span>{{ item.summary }}</span>
                    <span class="sr-only">Waiting to upload</span>
                </li>
            </ul>
        </section>

        <section class="space-y-3" data-tour="log-manual">
            <Link
                :href="scan()"
                class="bg-nav text-nav-foreground flex min-h-20 cursor-pointer items-center gap-4 rounded-[1.75rem] px-5 text-left shadow-[0_10px_24px_-10px_rgb(28_25_23_/_0.45)] transition-transform duration-200 active:scale-[0.98]"
                data-test="scan"
                data-tour="scan-photo"
            >
                <span
                    class="bg-cta text-cta-foreground flex size-14 shrink-0 items-center justify-center rounded-full"
                    aria-hidden="true"
                >
                    <Camera class="size-7" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-xl font-bold">Photograph the meter</span>
                    <span class="text-nav-muted mt-0.5 block text-base">
                        Blood pressure or sugar screen
                    </span>
                </span>
                <Plus class="size-6 shrink-0" aria-hidden="true" />
            </Link>

            <Link
                :href="logBp()"
                class="hl-card flex min-h-16 cursor-pointer items-center gap-3 px-4 py-3 transition-transform duration-200 active:scale-[0.98]"
                data-test="log-bp"
            >
                <span
                    class="bg-primary text-primary-foreground flex size-12 shrink-0 items-center justify-center rounded-full"
                    aria-hidden="true"
                >
                    <Activity class="size-6" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-lg font-bold">Blood pressure</span>
                    <span class="text-muted-foreground block text-base">
                        Type the cuff numbers
                    </span>
                </span>
                <ChevronRight class="size-6 shrink-0" aria-hidden="true" />
            </Link>

            <Link
                :href="logSugar()"
                class="hl-card flex min-h-16 cursor-pointer items-center gap-3 px-4 py-3 transition-transform duration-200 active:scale-[0.98]"
                data-test="log-sugar"
            >
                <span
                    class="bg-success text-success-foreground flex size-12 shrink-0 items-center justify-center rounded-full"
                    aria-hidden="true"
                >
                    <Droplet class="size-6" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-lg font-bold">Blood sugar</span>
                    <span class="text-muted-foreground block text-base">
                        Type the meter number
                    </span>
                </span>
                <ChevronRight class="size-6 shrink-0" aria-hidden="true" />
            </Link>

            <Link
                :href="logFood()"
                class="hl-card flex min-h-16 cursor-pointer items-center gap-3 px-4 py-3 transition-transform duration-200 active:scale-[0.98]"
                data-test="log-food"
            >
                <span
                    class="bg-accent text-accent-foreground flex size-12 shrink-0 items-center justify-center rounded-full"
                    aria-hidden="true"
                >
                    <Utensils class="size-6" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-lg font-bold">Food</span>
                    <span class="text-muted-foreground block text-base">
                        Photograph, search, or barcode
                    </span>
                </span>
                <ChevronRight class="size-6 shrink-0" aria-hidden="true" />
            </Link>
        </section>

        <section
            class="hl-card px-4 pt-5 pb-4"
            aria-live="polite"
            data-tour="today"
        >
            <TodayGauge
                :count="todayCount"
                :target="todayTarget"
                :date-label="dateLabel"
                :status-text="todayStatusText"
            />
            <div class="mt-1 grid grid-cols-3 gap-2">
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
                    <span class="mt-1 text-sm font-medium">{{
                        slot.label
                    }}</span>
                    <span class="sr-only">
                        {{ slot.filled ? 'logged' : 'not yet' }}
                    </span>
                </div>
            </div>
            <button
                v-if="readAloud && summarySpeech"
                type="button"
                class="text-primary mt-2 inline-flex min-h-14 w-full items-center justify-center gap-2 rounded-2xl text-base font-medium underline-offset-4 hover:underline"
                data-test="read-summary"
                @click="speak(summarySpeech)"
            >
                <Volume2 class="size-5" aria-hidden="true" />
                Read today's summary
            </button>
        </section>

        <section
            v-if="afterMealsPreview.length > 0"
            class="hl-card px-4 py-4"
            data-test="food-response"
        >
            <h2 class="text-xl font-bold">After food</h2>
            <p class="text-muted-foreground mt-1 text-base leading-relaxed">
                {{ foodResponse.intro }}
            </p>
            <ul class="mt-3 space-y-2 text-base leading-relaxed">
                <li
                    v-for="row in afterMealsPreview"
                    :key="row.line"
                    class="tabular-nums"
                >
                    {{ row.line }}
                </li>
            </ul>
            <Link
                :href="trends()"
                class="text-primary mt-3 inline-flex min-h-14 items-center text-base font-medium underline-offset-4 hover:underline"
            >
                See all on Trends
            </Link>
        </section>

        <section v-if="recent.length > 0" class="space-y-3">
            <h2 class="text-xl font-bold">Last readings</h2>
            <ReadingRow
                v-for="entry in recent"
                :key="`${entry.type}-${entry.id}`"
                :entry="entry"
            />
        </section>

        <p class="text-muted-foreground mt-auto pt-2 text-sm leading-relaxed">
            This app is a record-keeping tool, not a medical device, and does
            not provide medical advice. If a reading is 180/120 or higher, or if
            you feel unwell, seek immediate medical attention.
            <Link
                :href="home.url({ query: { guide: '1' } })"
                class="text-foreground ml-1 inline-flex min-h-11 items-center font-semibold underline-offset-4 hover:underline"
                data-test="open-guide"
            >
                How to use this app
            </Link>
        </p>
    </div>
</template>
