<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { rememberSystemTimezone, systemTimezone } from '@/lib/systemClock';
import { login } from '@/routes';
import { store } from '@/routes/register';

const props = defineProps<{
    timezones: Record<string, string>;
    aliases?: Record<string, string>;
    defaultTimezone: string;
}>();

const selectedTimezone = ref(props.defaultTimezone);
const detectedTimezone = ref('');
const glucoseUnit = ref('mg_dl');
const weightUnit = ref('kg');

onMounted(() => {
    const browser = rememberSystemTimezone() || systemTimezone();
    detectedTimezone.value = browser;

    if (selectedTimezone.value !== '') {
        return;
    }

    const match = browser !== '' ? props.aliases?.[browser] : undefined;

    if (match && props.timezones[match]) {
        selectedTimezone.value = match;
    }
});

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'We will email you a login link. You never need a password.',
    },
});
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name" class="text-base">Name</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    name="name"
                    placeholder="Your name"
                    class="h-12 text-lg md:text-lg"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email" class="text-base">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                    class="h-12 text-lg md:text-lg"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <input
                    type="hidden"
                    name="detected_timezone"
                    :value="detectedTimezone"
                />
                <Label for="timezone" class="text-base">Country</Label>
                <select
                    id="timezone"
                    name="timezone"
                    required
                    :value="selectedTimezone"
                    class="border-input bg-background h-12 w-full rounded-md border px-3 text-lg"
                    @change="selectedTimezone = ($event.target as HTMLSelectElement).value"
                >
                    <option value="" disabled>Choose your country</option>
                    <option
                        v-for="(label, value) in props.timezones"
                        :key="value"
                        :value="value"
                    >
                        {{ label }}
                    </option>
                </select>
                <InputError :message="errors.timezone" />
            </div>

            <div class="grid gap-2">
                <p class="text-base font-medium">Glucose units</p>
                <input type="hidden" name="glucose_unit" :value="glucoseUnit" />
                <div class="grid grid-cols-2 gap-3">
                    <Button
                        type="button"
                        class="h-12 text-base"
                        :variant="glucoseUnit === 'mg_dl' ? 'default' : 'outline'"
                        @click="glucoseUnit = 'mg_dl'"
                    >
                        mg/dL
                    </Button>
                    <Button
                        type="button"
                        class="h-12 text-base"
                        :variant="glucoseUnit === 'mmol_l' ? 'default' : 'outline'"
                        @click="glucoseUnit = 'mmol_l'"
                    >
                        mmol/L
                    </Button>
                </div>
                <InputError :message="errors.glucose_unit" />
            </div>

            <div class="grid gap-2">
                <p class="text-base font-medium">Weight units</p>
                <input type="hidden" name="weight_unit" :value="weightUnit" />
                <div class="grid grid-cols-2 gap-3">
                    <Button
                        type="button"
                        class="h-12 text-base"
                        :variant="weightUnit === 'kg' ? 'default' : 'outline'"
                        @click="weightUnit = 'kg'"
                    >
                        kg
                    </Button>
                    <Button
                        type="button"
                        class="h-12 text-base"
                        :variant="weightUnit === 'lb' ? 'default' : 'outline'"
                        @click="weightUnit = 'lb'"
                    >
                        lb
                    </Button>
                </div>
                <InputError :message="errors.weight_unit" />
            </div>

            <Button
                type="submit"
                class="mt-2 h-14 w-full rounded-full text-lg"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Email me a login link
            </Button>
        </div>

        <div class="text-muted-foreground text-center text-base">
            Already have an account?
            <TextLink :href="login()">Log in</TextLink>
        </div>
    </Form>
</template>
