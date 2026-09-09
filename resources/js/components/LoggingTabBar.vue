<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Camera, ChartLine, CircleUser, ClipboardList, House } from '@lucide/vue';
import { computed } from 'vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { history, home, scan, trends } from '@/routes';

const page = usePage();
const user = computed(() => page.props.auth.user);
const onHome = computed(() => page.component === 'Home');
const onHistory = computed(() => page.component === 'readings/History');
const onScan = computed(() => page.component === 'readings/Scan');
const onTrends = computed(() => page.component === 'readings/Trends');

const tabClass = (active: boolean): string =>
    [
        'flex min-h-16 min-w-[44px] cursor-pointer flex-col items-center justify-center gap-1 rounded-xl px-1 text-sm font-medium',
        active ? 'text-nav-foreground' : 'text-nav-muted',
    ].join(' ');
</script>

<template>
    <nav
        class="bg-nav text-nav-foreground fixed inset-x-0 bottom-0 z-20 rounded-t-[1.75rem] pb-[max(0.5rem,env(safe-area-inset-bottom))] shadow-[0_-8px_28px_-12px_rgb(28_25_23_/_0.35)]"
        aria-label="Main"
        data-tour="tabs"
    >
        <div class="mx-auto grid max-w-md grid-cols-5 items-end px-1 pt-2">
            <Link
                :href="home()"
                :class="tabClass(onHome)"
                :aria-current="onHome ? 'page' : undefined"
            >
                <House
                    class="size-6"
                    :stroke-width="onHome ? 2.5 : 2"
                    aria-hidden="true"
                />
                Home
            </Link>

            <Link
                :href="history()"
                :class="tabClass(onHistory)"
                :aria-current="onHistory ? 'page' : undefined"
                data-tour="tab-history"
            >
                <ClipboardList
                    class="size-6"
                    :stroke-width="onHistory ? 2.5 : 2"
                    aria-hidden="true"
                />
                History
            </Link>

            <Link
                :href="scan()"
                class="flex min-h-16 cursor-pointer flex-col items-center justify-end gap-1 pb-1 text-sm font-medium"
                :class="onScan ? 'text-nav-foreground' : 'text-nav-muted'"
                :aria-current="onScan ? 'page' : undefined"
            >
                <span
                    class="bg-cta text-cta-foreground -mt-7 flex size-16 items-center justify-center rounded-full shadow-[0_8px_20px_-6px_rgb(194_65_12_/_0.7)] ring-4 ring-[var(--nav)]"
                    :class="onScan ? 'ring-white/30' : ''"
                >
                    <Camera class="size-7" stroke-width="2.25" aria-hidden="true" />
                </span>
                Scan
            </Link>

            <Link
                :href="trends()"
                :class="tabClass(onTrends)"
                :aria-current="onTrends ? 'page' : undefined"
            >
                <ChartLine
                    class="size-6"
                    :stroke-width="onTrends ? 2.5 : 2"
                    aria-hidden="true"
                />
                Trends
            </Link>

            <DropdownMenu>
                <DropdownMenuTrigger
                    :class="tabClass(false)"
                    aria-label="Account menu"
                    data-tour="tab-account"
                >
                    <CircleUser class="size-6" aria-hidden="true" />
                    Account
                </DropdownMenuTrigger>
                <DropdownMenuContent align="center" side="top" class="mb-2 w-56">
                    <UserMenuContent v-if="user" :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </nav>
</template>
