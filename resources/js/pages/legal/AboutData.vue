<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home, privacy } from '@/routes';
</script>

<template>
    <Head title="Data sources" />

    <div
        class="bg-background text-foreground mx-auto flex min-h-dvh w-full max-w-md flex-col px-5 py-[max(1.5rem,env(safe-area-inset-bottom))]"
    >
        <Link :href="home()" class="self-start">
            <AppLogoIcon class="size-16" />
        </Link>
        <h1 class="mt-6 text-3xl font-bold">Data sources and licences</h1>
        <p class="text-muted-foreground mt-2 text-base leading-relaxed">
            Required so you can see who processes data, and so food data is
            attributed under Open Database Licence (ODbL).
        </p>
        <ul class="mt-6 space-y-4 text-base leading-relaxed">
            <li>
                <span class="font-semibold">Google Gemini.</span>
                Reads numbers from a meter photo, or names food in a photo.
                Images only. Deleted after extract. Google API terms.
            </li>
            <li>
                <span class="font-semibold">Resend.</span>
                Sends the login email. Name and email only. Resend terms.
            </li>
            <li>
                <span class="font-semibold">Laravel Cloud.</span>
                Hosts the app and database in the cloud. Laravel Cloud terms.
            </li>
            <li>
                <span class="font-semibold">Browser push.</span>
                Mozilla, Google, or Apple deliver “time to log” notices to this
                phone when you allow notifications. The push address only — not
                your readings.
            </li>
            <li>
                <span class="font-semibold">Open Food Facts.</span>
                Worldwide packaged-food database. Barcode and name search. Shown
                with “Data from Open Food Facts (ODbL)”. We send the barcode or
                search words only — not your name, email, or date of birth.
            </li>
        </ul>
        <p class="mt-8 text-base">
            <Link :href="privacy()" class="font-semibold underline">
                Privacy notice
            </Link>
            <span class="text-muted-foreground"> · </span>
            <Link :href="home()" class="font-semibold underline">Home</Link>
        </p>
    </div>
</template>
