<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Activity, Camera, Droplet } from '@lucide/vue';
import { login } from '@/routes';
import { register } from '@/routes';
</script>

<template>
    <Head title="HealthLog" />

    <div
        class="bg-background text-foreground flex min-h-dvh flex-col px-5 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-[max(1.5rem,env(safe-area-inset-top))]"
    >
        <div class="mx-auto flex w-full max-w-md flex-1 flex-col">
            <p class="text-primary text-sm font-bold tracking-wide uppercase">
                HealthLog
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
                <p class="text-muted-foreground text-center text-sm leading-relaxed">
                    This is a record-keeping tool, not a medical device, and does
                    not provide medical advice.
                </p>
            </div>
        </div>
    </div>
</template>
