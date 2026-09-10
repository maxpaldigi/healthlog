<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { CalendarDays } from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import InstallAppBanner from '@/components/InstallAppBanner.vue';
import LoggingTabBar from '@/components/LoggingTabBar.vue';
import OnboardingGuide from '@/components/OnboardingGuide.vue';
import { Toaster } from '@/components/ui/sonner';
import { home } from '@/routes';

const page = usePage();
const isHome = computed(() => page.component === 'Home');
const wide = computed(
    () =>
        page.component === 'readings/Report' ||
        page.component === 'clinic/Clinic',
);
const title = computed(() => {
    if (page.component === 'Home') {
        return 'Today';
    }

    if (page.component === 'readings/History') {
        return 'History';
    }

    if (page.component === 'readings/Trends') {
        return 'Trends';
    }

    if (page.component === 'readings/Scan') {
        return 'Scan';
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

    return 'HealthyMe';
});

const dateLabel = computed(() =>
    new Date().toLocaleDateString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    }),
);
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
            class="no-print mx-auto flex w-full items-center justify-between gap-3 px-4 pt-[max(1rem,env(safe-area-inset-top))] pb-2"
            :class="wide ? 'max-w-3xl' : 'max-w-md'"
            data-tour="brand"
        >
            <Link
                :href="home()"
                class="focus-visible:ring-ring flex min-h-11 min-w-0 items-center gap-2.5 rounded-2xl outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                aria-label="HealthyMe home"
            >
                <AppLogoIcon
                    class="size-11 shrink-0 shadow-sm ring-1 ring-black/5"
                    alt=""
                />
                <span
                    class="text-foreground truncate text-lg leading-tight font-bold"
                >
                    {{ isHome ? 'HealthyMe' : title }}
                </span>
            </Link>
            <p
                class="text-muted-foreground flex min-h-11 shrink-0 items-center gap-1.5 text-sm font-medium"
            >
                <CalendarDays class="size-4 shrink-0" aria-hidden="true" />
                <time class="whitespace-nowrap">{{ dateLabel }}</time>
            </p>
        </header>

        <main
            id="main"
            class="mx-auto flex w-full flex-1 flex-col px-4 pt-2 pb-36"
            :class="wide ? 'max-w-3xl' : 'max-w-md'"
        >
            <slot />
        </main>

        <LoggingTabBar class="no-print" />
        <InstallAppBanner />
        <OnboardingGuide />
        <Toaster />
    </div>
</template>
