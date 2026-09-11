<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Toaster } from '@/components/ui/sonner';
import { home, logout } from '@/routes';

defineOptions({
    title: 'Operator',
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
            class="mx-auto flex w-full max-w-3xl items-center justify-between gap-3 px-4 pt-[max(1rem,env(safe-area-inset-top))] pb-2"
        >
            <Link
                href="/admin"
                class="focus-visible:ring-ring flex min-h-14 min-w-0 items-center gap-2.5 rounded-2xl outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                aria-label="Operator home"
            >
                <AppLogoIcon
                    class="size-11 shrink-0 shadow-sm ring-1 ring-black/5"
                    alt=""
                />
                <span class="text-foreground truncate text-lg leading-tight font-bold">
                    Operator
                </span>
            </Link>
            <nav class="flex shrink-0 items-center gap-2">
                <Link
                    :href="home()"
                    class="text-muted-foreground hover:text-foreground min-h-12 rounded-xl px-3 py-2 text-base font-medium"
                >
                    My log
                </Link>
                <Link
                    :href="logout()"
                    method="post"
                    as="button"
                    class="text-muted-foreground hover:text-foreground min-h-12 rounded-xl px-3 py-2 text-base font-medium"
                >
                    Log out
                </Link>
            </nav>
        </header>

        <main
            id="main"
            class="mx-auto flex w-full max-w-3xl flex-1 flex-col px-4 pt-2 pb-[max(2rem,env(safe-area-inset-bottom))]"
        >
            <slot />
        </main>

        <Toaster />
    </div>
</template>
