<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';

const LAST_EMAIL_KEY = 'healthyme.last-login-email';

defineOptions({
    layout: {
        title: 'Log in',
        description: 'Enter your email. We send a one-time login link. After you open it, this phone stays signed in.',
    },
});

const props = defineProps<{
    status?: string;
    demoEmail?: string | null;
}>();

const email = ref('');

onMounted(() => {
    try {
        email.value = window.localStorage.getItem(LAST_EMAIL_KEY) ?? '';
    } catch {
        email.value = '';
    }
});

const rememberEmail = (): void => {
    const value = email.value.trim();

    try {
        if (value !== '') {
            window.localStorage.setItem(LAST_EMAIL_KEY, value);
        }
    } catch {
        // Private browsing can block storage.
    }
};
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
        @submit="rememberEmail"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email" class="text-base">Email address</Label>
                <Input
                    id="email"
                    v-model="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                    class="h-12 text-lg md:text-lg"
                    @change="rememberEmail"
                />
                <InputError :message="errors.email" />
                <p class="text-muted-foreground text-base leading-relaxed">
                    You only need a new link on a new phone, or after you log
                    out. This phone remembers the last email you used here.
                </p>
            </div>

            <Button
                type="submit"
                class="mt-2 h-14 w-full rounded-full text-lg"
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

    <Form
        v-if="props.demoEmail"
        v-bind="store.form()"
        v-slot="{ processing }"
        class="mt-4"
    >
        <input type="hidden" name="email" :value="props.demoEmail" />
        <Button
            type="submit"
            variant="secondary"
            class="h-14 w-full rounded-full text-lg"
            :disabled="processing"
            data-test="demo-login-button"
        >
            <Spinner v-if="processing" />
            Open the demo
        </Button>
        <p class="text-muted-foreground mt-3 text-center text-base leading-relaxed">
            Uses {{ props.demoEmail }} on this computer. No email is sent.
        </p>
    </Form>
</template>
