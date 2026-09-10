<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;
</script>

<template>
    <div
        class="bg-muted inline-flex w-full gap-1 rounded-2xl p-1"
        role="radiogroup"
        aria-label="Colour theme"
    >
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            type="button"
            role="radio"
            :aria-checked="appearance === value"
            class="flex min-h-14 flex-1 items-center justify-center rounded-xl px-3 text-base transition-colors"
            :class="
                appearance === value
                    ? 'bg-card text-foreground shadow-xs'
                    : 'text-muted-foreground hover:bg-background/60 hover:text-foreground'
            "
            @click="updateAppearance(value)"
        >
            <component :is="Icon" class="-ml-1 size-5" aria-hidden="true" />
            <span class="ml-1.5">{{ label }}</span>
        </button>
    </div>
</template>
