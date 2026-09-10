<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ChevronRight, Users } from '@lucide/vue';
import { current as switchProfile } from '@/routes/profiles';

type Person = {
    id: number;
    name: string;
    role: string | null;
    role_label: string | null;
    today_filled: number;
    today_target: number;
    last_reading: { title: string; when: string } | null;
    alerts: { key: string; message: string }[];
    needs_attention: boolean;
};

defineProps<{
    people: Person[];
    current_id: number;
}>();
</script>

<template>
    <Head title="People" />

    <div class="flex flex-1 flex-col gap-6">
        <header class="space-y-1">
            <h1 class="flex items-center gap-2 text-3xl font-bold">
                <Users class="size-8" aria-hidden="true" />
                People
            </h1>
            <p class="text-muted-foreground text-lg leading-relaxed">
                Every log you own or help with. They can always see who has
                access.
            </p>
        </header>

        <p
            v-if="people.length === 0"
            class="text-muted-foreground text-base leading-relaxed"
        >
            No logs yet.
        </p>

        <Form
            v-for="person in people"
            :key="person.id"
            v-bind="switchProfile.form({ profile: person.id })"
        >
            <input type="hidden" name="then" value="home" />
            <button
                type="submit"
                class="hl-card flex min-h-28 w-full cursor-pointer flex-col items-start gap-2 px-4 py-4 text-left"
                :class="
                    person.needs_attention
                        ? 'border-amber-500'
                        : person.id === current_id
                          ? 'ring-primary ring-2'
                          : ''
                "
                :data-test="`person-${person.id}`"
            >
                <span class="flex w-full items-center justify-between gap-2">
                    <span class="text-xl font-bold">{{ person.name }}</span>
                    <span class="text-muted-foreground text-base">
                        {{ person.role_label }}
                    </span>
                </span>
                <span class="text-base leading-relaxed">
                    {{ person.today_filled }} of {{ person.today_target }}
                    blood pressure readings today
                </span>
                <span
                    v-if="person.last_reading"
                    class="text-muted-foreground text-base"
                >
                    Last: {{ person.last_reading.title }} ·
                    {{ person.last_reading.when }}
                </span>
                <span v-else class="text-muted-foreground text-base">
                    No readings yet
                </span>
                <span
                    v-for="alert in person.alerts"
                    :key="alert.key"
                    class="text-base text-amber-900"
                >
                    {{ alert.message }}
                </span>
                <span
                    class="text-primary mt-1 inline-flex min-h-11 items-center gap-1 text-base font-semibold"
                >
                    Open
                    <ChevronRight class="size-5" aria-hidden="true" />
                </span>
            </button>
        </Form>

        <p class="text-muted-foreground text-sm leading-relaxed">
            Invite someone from Health profiles. They will be listed here after
            they accept, and the person whose log it is can always remove
            access.
        </p>
    </div>
</template>
