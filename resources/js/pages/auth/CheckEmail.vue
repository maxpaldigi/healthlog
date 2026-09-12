<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { login, register } from '@/routes';

defineOptions({
    layout: {
        title: 'Check your email',
        description:
            'If this email already has an account, we sent a one-time link. If nothing arrives, create an account first. We do not say whether an email is on HealthyMe.',
    },
});

defineProps<{
    loginUrl?: string | null;
    sendsRealMail?: boolean;
    mailFailed?: boolean;
    demoLogin?: boolean;
}>();
</script>

<template>
    <Head title="Check your email" />

    <div class="flex flex-col gap-6">
        <p
            v-if="mailFailed"
            class="text-destructive text-center text-base leading-relaxed"
        >
            We could not send the email. Use the button below, or fix mail in
            Laravel Cloud and try again.
        </p>
        <p class="text-muted-foreground text-center text-base leading-relaxed">
            If this email already has an account, we sent a one-time login
            link. It expires in 15 minutes and works once. After you open it,
            this phone stays signed in.
            <template v-if="sendsRealMail && !mailFailed">
                Check your inbox and spam.
            </template>
            <template v-else-if="!mailFailed">
                On this computer mail is not sent to a real inbox yet.
            </template>
        </p>
        <p class="text-center text-base leading-relaxed">
            No email? You need an account before we can send a login link.
            Create one first — we will not tell anyone whether an address is
            already on HealthyMe.
        </p>

        <a v-if="loginUrl" :href="loginUrl" class="block">
            <Button class="h-14 w-full rounded-full text-lg" data-test="open-login-link">
                Open login link
            </Button>
        </a>

        <Link :href="register()" class="block">
            <Button
                type="button"
                variant="outline"
                class="h-14 w-full rounded-full text-lg"
                data-test="check-email-register"
            >
                Create an account first
            </Button>
        </Link>

        <p class="text-muted-foreground text-center text-base leading-relaxed">
            Wrong email?
            <TextLink :href="login()">Try again</TextLink>
            <template v-if="demoLogin">
                On this computer you can also open the demo from Log in.
            </template>
        </p>
    </div>
</template>
