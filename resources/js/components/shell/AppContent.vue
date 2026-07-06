<script setup lang="ts">
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <main class="flex-1 overflow-auto p-8">
        <Breadcrumbs
            v-if="breadcrumbs?.length"
            :breadcrumbs="breadcrumbs"
            class="mb-3"
        />
        <slot />
    </main>
</template>
