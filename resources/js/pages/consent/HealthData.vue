<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { privacy } from '@/routes';
import { store } from '@/routes/consent/health-data';

defineOptions({
    layout: {
        title: 'Health data',
        description:
            'We need your separate OK before we store blood pressure or sugar readings.',
    },
});

defineProps<{
    policyVersion: string;
}>();
</script>

<template>
    <Head title="Health data" />

    <Form
        v-bind="store.form()"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <p class="text-base leading-relaxed">
            HealthyMe stores blood pressure, blood sugar, optional height and
            weight, and related notes so you can keep a record and print a
            visit report. This is not bundled with any other agreement.
        </p>
        <p class="text-muted-foreground text-base leading-relaxed">
            Policy version {{ policyVersion }}. Read the
            <Link :href="privacy()" class="text-foreground font-semibold underline">
                privacy notice
            </Link>
            first.
        </p>

        <label class="flex items-start gap-3 text-base leading-relaxed">
            <input
                type="checkbox"
                name="health_data_consent"
                value="1"
                class="mt-1 size-6 shrink-0"
                data-test="health-data-consent"
            />
            <span>
                I agree that HealthyMe may store my health readings for this
                record-keeping purpose.
            </span>
        </label>
        <InputError :message="errors.health_data_consent" />

        <Button
            type="submit"
            class="h-14 w-full rounded-full text-lg"
            :disabled="processing"
            data-test="grant-health-data-consent"
        >
            <Spinner v-if="processing" />
            Save this choice
        </Button>
    </Form>
</template>
