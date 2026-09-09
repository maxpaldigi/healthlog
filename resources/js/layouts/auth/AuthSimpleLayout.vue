<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="bg-background flex min-h-dvh flex-col items-center justify-center gap-6 p-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-[max(1.5rem,env(safe-area-inset-top))] md:p-10"
    >
        <div class="w-full max-w-md">
            <div class="hl-card flex flex-col gap-8 p-6 md:p-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="bg-nav text-nav-foreground mb-1 flex h-14 w-14 items-center justify-center rounded-full"
                        >
                            <AppLogoIcon
                                class="size-8 fill-current"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <p class="text-primary text-sm font-bold tracking-wide uppercase">
                            HealthLog
                        </p>
                        <h1 class="text-2xl font-bold">{{ title }}</h1>
                        <p class="text-muted-foreground text-center text-base leading-relaxed">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
