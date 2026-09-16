<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    policy: Record<string, unknown>;
}>();
</script>

<template>
    <Head title="Edit policy" />

    <div class="flex flex-1 flex-col" />
</template>
