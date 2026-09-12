<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { useLargeText } from '@/composables/useLargeText';
import { useReadAloud } from '@/composables/useReadAloud';
import { useTranslator } from '@/composables/useTranslator';
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

const { t } = useTranslator();
const page = usePage();
const { largeText, updateLargeText } = useLargeText();
const { readAloud, updateReadAloud } = useReadAloud();

const chooseLocale = (value: string): void => {
    if (value === page.props.locale) {
        return;
    }

    router.patch('/settings/appearance/locale', { locale: value });
};
</script>

<template>
    <Head :title="t('appearance.heading')" />

    <h1 class="sr-only">{{ t('appearance.heading') }}</h1>

    <div class="space-y-8">
        <div class="space-y-6">
            <Heading
                variant="small"
                :title="t('appearance.heading')"
                :description="t('appearance.description')"
            />
            <AppearanceTabs />
        </div>

        <div class="space-y-3">
            <h2 class="text-lg font-semibold">{{ t('appearance.language') }}</h2>
            <p class="text-muted-foreground text-base leading-relaxed">
                {{ t('appearance.language_hint') }}
            </p>
            <div
                class="grid gap-2"
                role="radiogroup"
                :aria-label="t('appearance.language')"
            >
                <Button
                    v-for="option in page.props.locales"
                    :key="option.value"
                    type="button"
                    role="radio"
                    :aria-checked="page.props.locale === option.value"
                    class="h-14 justify-start text-base"
                    :variant="
                        page.props.locale === option.value
                            ? 'default'
                            : 'outline'
                    "
                    :data-test="`locale-${option.value}`"
                    @click="chooseLocale(option.value)"
                >
                    {{ option.label }}
                </Button>
            </div>
        </div>

        <div class="space-y-3">
            <h2 class="text-lg font-semibold">{{ t('appearance.easier') }}</h2>
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
                    <span class="block font-medium">{{
                        t('appearance.large_text')
                    }}</span>
                    <span class="text-muted-foreground mt-1 block">
                        {{ t('appearance.large_text_hint') }}
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
                    <span class="block font-medium">{{
                        t('appearance.read_aloud')
                    }}</span>
                    <span class="text-muted-foreground mt-1 block">
                        {{ t('appearance.read_aloud_hint') }}
                    </span>
                </span>
            </label>
        </div>
    </div>
</template>
