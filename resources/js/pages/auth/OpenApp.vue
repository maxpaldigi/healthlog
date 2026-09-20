<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const props = defineProps<{
    appUrl: string;
    webContinueUrl: string;
}>();

onMounted(() => {
    window.location.href = props.appUrl;
});
</script>

<template>
    <Head title="Open HealthyMe" />

    <div
        class="bg-background text-foreground flex min-h-dvh flex-col items-center justify-center px-5 py-10"
    >
        <div class="hl-card w-full max-w-md space-y-6 px-6 py-8 text-center">
            <AppLogoIcon class="mx-auto size-20" />
            <h1 class="text-2xl font-bold">Opening HealthyMe…</h1>
            <p class="text-muted-foreground text-lg leading-relaxed">
                Tap the button if the app does not open on its own. Your login
                link works once.
            </p>
            <a
                :href="appUrl"
                class="bg-nav text-nav-foreground flex min-h-14 w-full items-center justify-center rounded-full text-lg font-bold"
            >
                Open HealthyMe app
            </a>
            <a
                :href="webContinueUrl"
                class="text-muted-foreground block text-base underline"
            >
                Continue in the browser instead
            </a>
        </div>
    </div>
</template>
