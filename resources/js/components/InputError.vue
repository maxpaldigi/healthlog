<script setup lang="ts">
defineProps<{
    message?: string;
    id?: string;
}>();
</script>

<template>
    <div v-show="message">
        <p
            :id="id"
            class="text-destructive text-base"
            role="alert"
        >
            {{ message }}
        </p>
    </div>
</template>
