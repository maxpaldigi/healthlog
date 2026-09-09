<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChartLine, CircleUser, ClipboardList, House } from '@lucide/vue';
import { computed } from 'vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { history, home, trends } from '@/routes';

const page = usePage();
const user = computed(() => page.props.auth.user);
const onHome = computed(() => page.component === 'Home');
const onHistory = computed(() => page.component === 'readings/History');
const onTrends = computed(() => page.component === 'readings/Trends');
</script>

<template>
    <nav
        class="border-border bg-card/95 fixed inset-x-0 bottom-0 z-20 border-t pb-[max(0.5rem,env(safe-area-inset-bottom))] backdrop-blur-sm"
        aria-label="Main"
    >
        <div class="mx-auto grid max-w-md grid-cols-4 px-1 pt-1">
            <Link
                :href="home()"
                class="flex min-h-16 cursor-pointer flex-col items-center justify-center gap-1 rounded-xl text-sm font-medium"
                :class="
                    onHome
                        ? 'text-primary'
                        : 'text-muted-foreground'
                "
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
                class="flex min-h-16 cursor-pointer flex-col items-center justify-center gap-1 rounded-xl text-sm font-medium"
                :class="
                    onHistory
                        ? 'text-primary'
                        : 'text-muted-foreground'
                "
                :aria-current="onHistory ? 'page' : undefined"
            >
                <ClipboardList
                    class="size-6"
                    :stroke-width="onHistory ? 2.5 : 2"
                    aria-hidden="true"
                />
                History
            </Link>

            <Link
                :href="trends()"
                class="flex min-h-16 cursor-pointer flex-col items-center justify-center gap-1 rounded-xl text-sm font-medium"
                :class="
                    onTrends
                        ? 'text-primary'
                        : 'text-muted-foreground'
                "
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
                    class="text-muted-foreground flex min-h-16 cursor-pointer flex-col items-center justify-center gap-1 rounded-xl text-sm font-medium"
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
