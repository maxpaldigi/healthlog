<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home, privacy } from '@/routes';
import { data as aboutData } from '@/routes/about';
</script>

<template>
    <Head title="Terms" />

    <div
        class="bg-background text-foreground mx-auto flex min-h-dvh w-full max-w-md flex-col px-5 py-[max(1.5rem,env(safe-area-inset-bottom))]"
    >
        <Link :href="home()" class="self-start">
            <AppLogoIcon class="size-16" />
        </Link>
        <h1 class="mt-6 text-3xl font-bold">Terms of use</h1>
        <p class="text-muted-foreground mt-2 text-base leading-relaxed">
            Version 2026-09-10.
        </p>
        <div class="mt-6 space-y-4 text-base leading-relaxed">
            <p>
                HealthyMe is a personal logbook for blood pressure and blood
                sugar numbers. It is not a medical device. It does not provide
                medical advice and does not replace a doctor.
            </p>
            <p>
                You are responsible for the numbers you enter and for sharing a
                visit report only with people you choose.
            </p>
            <p>
                Agreeing to these terms does not grant permission to store
                health readings. That requires a separate health-data consent.
            </p>
        </div>
        <p class="text-muted-foreground mt-8 text-base">
            <Link :href="privacy()" class="text-foreground font-semibold underline">
                Privacy notice
            </Link>
            <span> · </span>
            <Link :href="aboutData()" class="text-foreground font-semibold underline">
                Data sources
            </Link>
            <span> · </span>
            <Link :href="home()" class="text-foreground font-semibold underline">
                Home
            </Link>
        </p>
    </div>
</template>
