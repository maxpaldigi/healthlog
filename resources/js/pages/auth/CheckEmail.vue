<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { login, register } from '@/routes';

defineOptions({
    layout: {
        title: 'Check your email',
        description: 'We sent a login link if that email has an account. It expires in 15 minutes and works once.',
    },
});

defineProps<{
    loginUrl?: string | null;
}>();
</script>

<template>
    <Head title="Check your email" />

    <div class="flex flex-col gap-6">
        <p class="text-muted-foreground text-center text-base leading-relaxed">
            This app signs you in with a link, not an OTP code. On this computer
            mail is not sent to a real inbox yet.
        </p>

        <a v-if="loginUrl" :href="loginUrl" class="block">
            <Button class="h-14 w-full rounded-full text-lg" data-test="open-login-link">
                Open login link
            </Button>
        </a>

        <p class="text-muted-foreground text-center text-base leading-relaxed">
            No button?
            <TextLink :href="login()">Try again</TextLink>
            or
            <TextLink :href="register()">create an account</TextLink>
            first. The demo login is demo@healthlog.test.
        </p>
    </div>
</template>
