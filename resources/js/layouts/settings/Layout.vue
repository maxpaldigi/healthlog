<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const onHub = computed(() => page.component === 'settings/Index');
</script>

<template>
    <div class="flex flex-1 flex-col gap-6">
        <p v-if="!onHub">
            <Link
                href="/settings"
                class="text-foreground inline-flex min-h-14 items-center text-base font-semibold underline-offset-4 hover:underline"
            >
                Settings
            </Link>
        </p>
        <slot />
    </div>
</template>
