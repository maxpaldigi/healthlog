<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/security';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Security settings',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Security settings" />

    <h1 class="sr-only">Security settings</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Sign-in"
            description="HealthLog uses an email login link. You never need a password."
        />

        <p class="text-muted-foreground text-base leading-relaxed">
            Each time you sign in we email a one-time link that expires in 15
            minutes. Keep access to the email address on your account.
        </p>
    </div>
</template>
