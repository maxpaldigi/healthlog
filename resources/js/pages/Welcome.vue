<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Activity, Camera, Droplet } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import { Spinner } from '@/components/ui/spinner';
import { useTranslator } from '@/composables/useTranslator';
import { login, privacy, register } from '@/routes';
import { data as aboutData } from '@/routes/about';
import { store } from '@/routes/login';

const { t } = useTranslator();

defineProps<{
    demoEmail?: string | null;
}>();
</script>

<template>
    <Head title="HealthyMe" />

    <div
        class="bg-background text-foreground flex min-h-dvh flex-col px-5 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-[max(1.5rem,env(safe-area-inset-top))]"
    >
        <div class="mx-auto flex w-full max-w-md flex-1 flex-col">
            <div class="flex items-start justify-between gap-3">
                <AppLogoIcon class="size-24" />
                <LocaleSwitcher />
            </div>
            <p class="text-primary mt-4 text-sm font-bold tracking-wide uppercase">
                HealthyMe
            </p>
            <h1 class="mt-3 text-4xl font-bold leading-tight">
                Blood pressure and sugar, easy to keep
            </h1>
            <p class="text-muted-foreground mt-3 text-lg leading-relaxed">
                Photograph the meter screen or type the numbers. Built for large
                type, clear buttons, and a visit report you can print for your
                doctor.
            </p>

            <ul class="mt-8 space-y-4">
                <li class="hl-card flex items-start gap-4 px-4 py-4">
                    <span
                        class="bg-cta text-cta-foreground flex size-12 shrink-0 items-center justify-center rounded-full"
                    >
                        <Camera class="size-6" aria-hidden="true" />
                    </span>
                    <span>
                        <span class="block text-lg font-bold">Take a photo</span>
                        <span class="text-muted-foreground mt-0.5 block text-base leading-relaxed">
                            Point the camera at a BP monitor or glucometer. You always check before anything is saved.
                        </span>
                    </span>
                </li>
                <li class="hl-card flex items-start gap-4 px-4 py-4">
                    <span
                        class="bg-primary text-primary-foreground flex size-12 shrink-0 items-center justify-center rounded-full"
                    >
                        <Activity class="size-6" aria-hidden="true" />
                    </span>
                    <span>
                        <span class="block text-lg font-bold">Three BP check-ins</span>
                        <span class="text-muted-foreground mt-0.5 block text-base leading-relaxed">
                            Morning, afternoon, and evening — the way many clinics ask for it.
                        </span>
                    </span>
                </li>
                <li class="hl-card flex items-start gap-4 px-4 py-4">
                    <span
                        class="bg-success text-success-foreground flex size-12 shrink-0 items-center justify-center rounded-full"
                    >
                        <Droplet class="size-6" aria-hidden="true" />
                    </span>
                    <span>
                        <span class="block text-lg font-bold">Sugar alongside BP</span>
                        <span class="text-muted-foreground mt-0.5 block text-base leading-relaxed">
                            One list, one trends view, one printout. No Bluetooth setup.
                        </span>
                    </span>
                </li>
            </ul>

            <div class="mt-auto flex flex-col gap-3 pt-10">
                <Link
                    :href="register()"
                    class="bg-nav text-nav-foreground flex min-h-14 cursor-pointer items-center justify-center rounded-full text-lg font-bold"
                >
                    Get started
                </Link>
                <Link
                    :href="login()"
                    class="border-border bg-card flex min-h-14 cursor-pointer items-center justify-center rounded-full border text-lg font-bold"
                >
                    Log in
                </Link>
                <Form
                    v-if="demoEmail"
                    v-bind="store.form()"
                    v-slot="{ processing }"
                >
                    <input type="hidden" name="email" :value="demoEmail" />
                    <button
                        type="submit"
                        class="border-border bg-card flex min-h-14 w-full cursor-pointer items-center justify-center rounded-full border text-lg font-bold disabled:opacity-60"
                        :disabled="processing"
                        data-test="demo-login-button"
                    >
                        <Spinner v-if="processing" />
                        {{ t('auth.login.demo') }}
                    </button>
                </Form>
                <p class="text-muted-foreground text-center text-sm leading-relaxed">
                    This is a record-keeping tool, not a medical device. It does
                    not provide medical advice and does not replace a doctor.
                </p>
                <p class="text-muted-foreground text-center text-sm">
                    <Link :href="privacy()" class="underline">Privacy</Link>
                    ·
                    <Link :href="aboutData()" class="underline">Data sources</Link>
                </p>
            </div>
        </div>
    </div>
</template>
