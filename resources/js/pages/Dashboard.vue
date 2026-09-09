<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <div class="space-y-2">
            <h1 class="text-2xl font-medium">Welcome to HealthLog</h1>
            <p class="text-muted-foreground max-w-xl text-base leading-relaxed">
                Your account is ready. This is a record-keeping tool for blood
                pressure and blood glucose readings. Logging screens come next.
            </p>
        </div>

        <p class="text-muted-foreground max-w-xl text-sm leading-relaxed">
            This app is a record-keeping tool, not a medical device, and does
            not provide medical advice. If a reading is 180/120 or higher, or
            if you feel unwell, seek immediate medical attention.
        </p>
    </div>
</template>
