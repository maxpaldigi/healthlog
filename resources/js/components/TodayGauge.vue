<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    count: number;
    target: number;
    dateLabel: string;
    statusText: string;
}>();

const percent = computed(() => {
    if (props.target <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((props.count / props.target) * 100));
});

const complete = computed(() => props.count >= props.target && props.target > 0);
</script>

<template>
    <div class="flex flex-col items-center">
        <div
            class="relative w-full max-w-[280px]"
            role="img"
            :aria-label="statusText"
        >
            <svg
                viewBox="0 0 240 148"
                class="h-auto w-full"
                aria-hidden="true"
            >
                <path
                    d="M 28 124 A 92 92 0 0 1 212 124"
                    fill="none"
                    class="stroke-[var(--gauge-track)]"
                    stroke-width="18"
                    stroke-linecap="round"
                />
                <path
                    v-if="percent > 0"
                    d="M 28 124 A 92 92 0 0 1 212 124"
                    fill="none"
                    class="stroke-[var(--gauge-fill)] motion-safe:transition-[stroke-dasharray] motion-safe:duration-300 motion-safe:ease-out"
                    stroke-width="18"
                    stroke-linecap="round"
                    pathLength="100"
                    :stroke-dasharray="`${percent} 100`"
                />
            </svg>
            <div
                class="absolute inset-x-0 bottom-1 flex flex-col items-center px-8 text-center"
            >
                <p class="text-muted-foreground text-sm font-medium">
                    {{ dateLabel }}
                </p>
                <p
                    class="mt-0.5 text-4xl font-bold tabular-nums leading-none"
                    data-test="today-status"
                >
                    <template v-if="complete"> {{ count }} logged </template>
                    <template v-else> {{ count }} of {{ target }} </template>
                </p>
                <p class="text-muted-foreground mt-1.5 text-base">
                    {{ complete ? 'BP check-ins today' : 'BP readings today' }}
                </p>
            </div>
        </div>
    </div>
</template>
