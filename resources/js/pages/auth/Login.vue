<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';

defineOptions({
    layout: {
        title: 'Log in',
        description: 'Enter your email. We send a login link — there is no password and no OTP code.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Log in" />

    <div
        v-if="status"
        class="mb-4 text-center text-base font-medium text-green-600"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email" class="text-base">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                    class="h-12 text-lg md:text-lg"
                />
                <InputError :message="errors.email" />
            </div>

            <Button
                type="submit"
                class="mt-2 h-14 w-full text-lg"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Email me a login link
            </Button>
        </div>

        <div class="text-muted-foreground text-center text-base">
            Don't have an account?
            <TextLink :href="register()">Create one</TextLink>
        </div>
    </Form>
</template>
