<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslator } from '@/composables/useTranslator';
import { register } from '@/routes';
import { store } from '@/routes/login';

const { t } = useTranslator();

const LAST_EMAIL_KEY = 'healthyme.last-login-email';

defineOptions({
    layout: {
        title: 'auth.login.title',
        description: 'auth.login.description',
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
    <Head :title="t('auth.login.title')" />

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
                <Label for="email" class="text-base">{{ t('auth.login.email') }}</Label>
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
                    {{ t('auth.login.remember_hint') }}
                </p>
            </div>

            <Button
                type="submit"
                class="mt-2 h-14 w-full rounded-full text-lg"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                {{ t('auth.login.submit') }}
            </Button>
        </div>

        <div class="text-center">
            <p class="text-muted-foreground mb-2 text-base">
                {{ t('auth.login.no_account') }}
            </p>
            <Link :href="register()" class="block">
                <Button
                    type="button"
                    variant="outline"
                    class="h-14 w-full rounded-full text-lg"
                    data-test="login-register-first"
                >
                    {{ t('auth.login.create') }}
                </Button>
            </Link>
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
            {{ t('auth.login.demo') }}
        </Button>
        <p class="text-muted-foreground mt-3 text-center text-base leading-relaxed">
            {{ t('auth.login.demo_hint', { email: props.demoEmail ?? '' }) }}
        </p>
    </Form>
</template>
