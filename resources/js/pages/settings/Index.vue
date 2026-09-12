<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight, HeartPulse, Shield, SwatchBook, User } from '@lucide/vue';
import type { Component } from 'vue';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editHealth } from '@/routes/health-profiles';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

const items: {
    title: string;
    hint: string;
    href: ReturnType<typeof editProfile>;
    icon: Component;
}[] = [
    {
        title: 'Profile',
        hint: 'Name and email',
        href: editProfile(),
        icon: User,
    },
    {
        title: 'Health profiles',
        hint: 'People, units, and reminders',
        href: editHealth(),
        icon: HeartPulse,
    },
    {
        title: 'Security',
        hint: 'How you sign in',
        href: editSecurity(),
        icon: Shield,
    },
    {
        title: 'Appearance',
        hint: 'Text size and this phone',
        href: editAppearance(),
        icon: SwatchBook,
    },
];
</script>

<template>
    <Head title="Settings" />

    <div class="flex flex-1 flex-col gap-6">
        <header class="space-y-1">
            <h1 class="text-3xl font-bold">Settings</h1>
            <p class="text-muted-foreground text-lg leading-relaxed">
                Name, this phone, and who can see a log.
            </p>
        </header>

        <nav class="space-y-3" aria-label="Settings">
            <Link
                v-for="item in items"
                :key="item.title"
                :href="item.href"
                class="hl-card flex min-h-16 cursor-pointer items-center gap-3 px-4 py-3 transition-transform duration-200 active:scale-[0.98]"
            >
                <span
                    class="bg-primary text-primary-foreground flex size-12 shrink-0 items-center justify-center rounded-full"
                    aria-hidden="true"
                >
                    <component :is="item.icon" class="size-6" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-lg font-bold">{{ item.title }}</span>
                    <span class="text-muted-foreground block text-base">
                        {{ item.hint }}
                    </span>
                </span>
                <ChevronRight class="size-6 shrink-0" aria-hidden="true" />
            </Link>
        </nav>
    </div>
</template>
