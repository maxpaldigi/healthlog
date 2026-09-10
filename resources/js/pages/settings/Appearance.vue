<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { useLargeText } from '@/composables/useLargeText';
import { useReadAloud } from '@/composables/useReadAloud';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Appearance settings',
                href: edit(),
            },
        ],
    },
});

const { largeText, updateLargeText } = useLargeText();
const { readAloud, updateReadAloud } = useReadAloud();
</script>

<template>
    <Head title="Appearance settings" />

    <h1 class="sr-only">Appearance settings</h1>

    <div class="space-y-8">
        <div class="space-y-6">
            <Heading
                variant="small"
                title="Appearance settings"
                description="Colours, text size, and read aloud for this phone."
            />
            <AppearanceTabs />
        </div>

        <div class="space-y-3">
            <h2 class="text-lg font-semibold">Easier to read</h2>
            <label
                class="border-border flex min-h-14 cursor-pointer items-start gap-3 rounded-2xl border px-4 py-3 text-base leading-relaxed"
            >
                <input
                    type="checkbox"
                    class="mt-1 size-7 shrink-0"
                    :checked="largeText"
                    data-test="large-text"
                    @change="
                        updateLargeText(
                            ($event.target as HTMLInputElement).checked,
                        )
                    "
                />
                <span>
                    <span class="block font-medium">Large text</span>
                    <span class="text-muted-foreground mt-1 block">
                        Makes words and buttons bigger on this phone.
                    </span>
                </span>
            </label>
            <label
                class="border-border flex min-h-14 cursor-pointer items-start gap-3 rounded-2xl border px-4 py-3 text-base leading-relaxed"
            >
                <input
                    type="checkbox"
                    class="mt-1 size-7 shrink-0"
                    :checked="readAloud"
                    data-test="read-aloud"
                    @change="
                        updateReadAloud(
                            ($event.target as HTMLInputElement).checked,
                        )
                    "
                />
                <span>
                    <span class="block font-medium">Read aloud</span>
                    <span class="text-muted-foreground mt-1 block">
                        This phone speaks saved numbers and today’s summary.
                        Speech stays on this device. Off by default. Press
                        Escape to stop.
                    </span>
                </span>
            </label>
        </div>
    </div>
</template>
