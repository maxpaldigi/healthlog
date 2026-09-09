<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import LoggingTabBar from '@/components/LoggingTabBar.vue';
import { Toaster } from '@/components/ui/sonner';

const page = usePage();
const isHome = computed(() => page.component === 'Home');
const wide = computed(
    () =>
        page.component === 'readings/Report' ||
        page.component === 'clinic/Clinic',
);
const title = computed(() => {
    if (page.component === 'readings/History') {
        return 'History';
    }

    if (page.component === 'readings/Trends') {
        return 'Trends';
    }

    if (page.component === 'readings/Report') {
        return 'Visit report';
    }

    if (page.component === 'clinic/Clinic') {
        return 'Clinic extras';
    }

    if (page.component.startsWith('readings/')) {
        return 'Log';
    }

    return 'HealthLog';
});
</script>

<template>
    <div class="bg-background flex min-h-dvh flex-col">
        <a
            href="#main"
            class="bg-primary text-primary-foreground sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-xl focus:px-4 focus:py-3"
        >
            Skip to main content
        </a>

        <header
            class="no-print px-4 pt-[max(1rem,env(safe-area-inset-top))] pb-1"
        >
            <p
                v-if="isHome"
                class="text-primary text-sm font-bold tracking-wide uppercase"
            >
                HealthLog
            </p>
            <p v-else class="text-muted-foreground text-sm font-medium">
                {{ title }}
            </p>
        </header>

        <main
            id="main"
            class="mx-auto flex w-full flex-1 flex-col px-4 pt-2 pb-28"
            :class="wide ? 'max-w-3xl' : 'max-w-md'"
        >
            <slot />
        </main>

        <LoggingTabBar class="no-print" />
        <Toaster />
    </div>
</template>
