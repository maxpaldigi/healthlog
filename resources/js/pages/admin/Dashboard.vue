<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type AccountRow = {
    id: number;
    name: string;
    email: string;
    joined_at: string;
    last_logged_at: string | null;
    people_count: number;
    bp_count: number;
    glucose_count: number;
    health_consent: boolean;
    is_super_admin: boolean;
};

const props = defineProps<{
    totals: {
        users: number;
        people: number;
        bp_readings: number;
        glucose_readings: number;
    };
    users: AccountRow[];
}>();

const query = ref('');

const visible = computed(() => {
    const needle = query.value.trim().toLowerCase();

    if (needle === '') {
        return props.users;
    }

    return props.users.filter(
        (row) =>
            row.name.toLowerCase().includes(needle) ||
            row.email.toLowerCase().includes(needle),
    );
});

const formatWhen = (value: string | null, empty: string): string => {
    if (value === null || value === '') {
        return empty;
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
};
</script>

<template>
    <Head title="People using this app" />

    <div class="flex flex-1 flex-col gap-6">
        <header class="space-y-1">
            <h1 class="text-3xl font-bold">People using this app</h1>
            <p class="text-muted-foreground text-lg leading-relaxed">
                Names and emails of accounts. This screen does not open anyone’s
                readings.
            </p>
        </header>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="hl-card p-4">
                <p class="text-muted-foreground text-base">Accounts</p>
                <p class="text-3xl font-bold" data-test="admin-user-count">
                    {{ totals.users }}
                </p>
            </div>
            <div class="hl-card p-4">
                <p class="text-muted-foreground text-base">People logs</p>
                <p class="text-3xl font-bold">{{ totals.people }}</p>
            </div>
            <div class="hl-card p-4">
                <p class="text-muted-foreground text-base">BP entries</p>
                <p class="text-3xl font-bold">{{ totals.bp_readings }}</p>
            </div>
            <div class="hl-card p-4">
                <p class="text-muted-foreground text-base">Sugar entries</p>
                <p class="text-3xl font-bold">{{ totals.glucose_readings }}</p>
            </div>
        </div>

        <label class="flex flex-col gap-2">
            <span class="text-base font-medium">Find someone</span>
            <input
                v-model="query"
                type="search"
                autocomplete="off"
                class="border-input bg-background min-h-14 rounded-2xl border px-4 text-lg"
                placeholder="Name or email"
                data-test="admin-user-search"
            />
        </label>

        <p
            v-if="visible.length === 0"
            class="text-muted-foreground text-base leading-relaxed"
        >
            No accounts match that search.
        </p>

        <ul class="flex flex-col gap-3">
            <li
                v-for="row in visible"
                :key="row.id"
                class="hl-card space-y-2 p-4"
                :data-test="`admin-user-${row.id}`"
            >
                <p class="flex flex-wrap items-baseline justify-between gap-2">
                    <span class="text-xl font-bold">{{ row.name }}</span>
                    <span
                        v-if="row.is_super_admin"
                        class="text-primary text-sm font-semibold tracking-wide uppercase"
                    >
                        Operator
                    </span>
                </p>
                <p class="text-lg break-all">{{ row.email }}</p>
                <p class="text-muted-foreground text-base leading-relaxed">
                    Joined {{ formatWhen(row.joined_at, '—') }}.
                    {{
                        row.last_logged_at
                            ? `Last log ${formatWhen(row.last_logged_at, '')}.`
                            : 'No readings yet.'
                    }}
                    {{ row.people_count }}
                    {{ row.people_count === 1 ? 'person' : 'people' }},
                    {{ row.bp_count }} BP,
                    {{ row.glucose_count }} sugar.
                    {{
                        row.health_consent
                            ? 'Health-data consent given.'
                            : 'Health-data consent not given.'
                    }}
                </p>
            </li>
        </ul>
    </div>
</template>
