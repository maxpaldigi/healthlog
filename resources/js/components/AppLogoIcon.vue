<script setup lang="ts">
defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <img
        src="/logo.png"
        alt="HealthyMe"
        v-bind="$attrs"
        class="rounded-full object-cover"
    />
</template>
