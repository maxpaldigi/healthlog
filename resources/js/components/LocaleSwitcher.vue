<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslator } from '@/composables/useTranslator';

const { t } = useTranslator();
const page = usePage();

const locales = computed(() => page.props.locales ?? []);
const current = computed(() => page.props.locale);
const show = computed(() => locales.value.length > 1);

const choose = (value: string): void => {
    if (value === current.value) {
        return;
    }

    router.patch('/locale', { locale: value }, { preserveScroll: true });
};
</script>

<template>
    <div
        v-if="show"
        class="flex shrink-0 items-center gap-1"
        role="radiogroup"
        :aria-label="t('appearance.language')"
        data-test="locale-switcher"
    >
        <button
            v-for="option in locales"
            :key="option.value"
            type="button"
            role="radio"
            :aria-checked="current === option.value"
            class="min-h-11 min-w-11 rounded-full px-3 text-sm font-bold transition-colors"
            :class="
                current === option.value
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-card text-foreground border-border border'
            "
            :data-test="`locale-switch-${option.value}`"
            @click="choose(option.value)"
        >
            {{ option.value.toUpperCase() }}
        </button>
    </div>
</template>
