<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home, privacy } from '@/routes';
import { data as aboutData } from '@/routes/about';
</script>

<template>
    <Head title="Privacy" />

    <div
        class="bg-background text-foreground mx-auto flex min-h-dvh w-full max-w-md flex-col px-5 py-[max(1.5rem,env(safe-area-inset-bottom))]"
    >
        <Link :href="home()" class="self-start">
            <AppLogoIcon class="size-16" />
        </Link>
        <h1 class="mt-6 text-3xl font-bold">Privacy notice</h1>
        <p class="text-muted-foreground mt-2 text-base leading-relaxed">
            Version 2026-09-11. HealthyMe is a record-keeping tool, not a
            medical device, and does not replace a doctor.
        </p>
        <div class="mt-6 space-y-4 text-base leading-relaxed">
            <p>
                We store your name, email, health profile settings, and the
                readings you add (blood pressure, sugar, food, optional weight,
                HbA1c, and medication names). Health data is only stored after a
                separate, unticked consent.
            </p>
            <p>
                Meter photos and food photos are sent to Google Gemini to read
                the numbers on a meter or to name the food. We send the image
                only — not your name, email, or date of birth — and we delete
                the photo after extract, whether that works or not.
            </p>
            <p>
                Login emails are sent through Resend. The app is hosted on
                Laravel Cloud.
            </p>
            <p>
                If you allow reminders on this phone, your browser gives us a
                push address so we can send “time to log” notices. We do not
                send your readings in that notice. You can turn this off in the
                browser settings.
            </p>
            <p>
                Readings typed while you are offline stay on this phone until
                the app can upload them.
            </p>
            <p>
                When you look up a food, we send the barcode or the search words
                to Open Food Facts. We do not send your name, email, or date of
                birth. The food you save stays in your account.
            </p>
            <p>
                When you look up a medication name, we send the search words to
                the U.S. National Library of Medicine RxNorm service. We do not
                send your name, email, or date of birth. You can still save a
                name we do not find.
            </p>
            <p>
                If you make a share link, anyone with that link can see the
                readings in the dates you chose until the link expires. A clinic
                reply link also lets them leave a note on the log and optional
                sugar targets. You will see that note at home and on the visit
                report. They cannot write after the link expires.
            </p>
            <p>
                If you invite a helper, we email them a link. They can view and
                add readings for that log. The person whose readings these are
                can always see who has access and can remove it.
            </p>
            <p>
                The person who runs this app can see who has an account (name,
                email, when you joined, whether you have logged, and how many
                entries you have). They cannot open your readings unless you
                share a link or invite them.
            </p>
            <p>
                If you turn on read aloud, this phone speaks saved numbers and
                today’s summary. The speech never leaves the device.
            </p>
            <p>
                You can log out on a shared phone. You can ask us to delete your
                account from Account settings.
            </p>
        </div>
        <p class="text-muted-foreground mt-8 text-base">
            <Link
                :href="aboutData()"
                class="text-foreground font-semibold underline"
            >
                Data sources and licences
            </Link>
            <span> · </span>
            <Link
                :href="home()"
                class="text-foreground font-semibold underline"
            >
                Home
            </Link>
            <span> · </span>
            <Link
                :href="privacy()"
                class="text-foreground font-semibold underline"
            >
                Privacy
            </Link>
        </p>
    </div>
</template>
