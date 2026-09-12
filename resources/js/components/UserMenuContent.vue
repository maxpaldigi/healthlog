<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { CircleHelp, LogOut, Settings, Shield, Users } from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { home, logout, people } from '@/routes';
import { edit as editHealth } from '@/routes/health-profiles';
import type { User } from '@/types';

type Props = {
    user: User;
};

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();

const isSuperAdmin = computed(
    () => Boolean(usePage().props.auth.is_super_admin),
);
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem v-if="isSuperAdmin" :as-child="true">
            <Link
                class="block w-full cursor-pointer"
                href="/admin"
                data-test="operator-dashboard-link"
            >
                <Shield class="mr-2 h-4 w-4" />
                Operator
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" href="/settings" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="people()" prefetch>
                <Users class="mr-2 h-4 w-4" />
                People
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true">
            <Link
                class="block w-full cursor-pointer"
                :href="editHealth()"
                prefetch
            >
                <Settings class="mr-2 h-4 w-4" />
                Health profiles
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true">
            <Link
                class="block w-full cursor-pointer"
                :href="home.url({ query: { guide: '1' } })"
                data-test="open-guide-menu"
            >
                <CircleHelp class="mr-2 h-4 w-4" />
                How to use this app
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
