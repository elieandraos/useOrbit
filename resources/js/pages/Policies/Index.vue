<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { Paginated } from '@/types/pagination';

defineProps<{
    policies: Paginated<Record<string, unknown>>;
}>();
</script>

<template>
    <Head title="Policies" />

    <div class="flex flex-1 flex-col" />
</template>
