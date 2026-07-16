<script setup lang="ts">
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    breadcrumbs?: BreadcrumbItem[];
    /** Opt-in: renders breadcrumbs in a bg-surface bar that bleeds edge-to-edge on mobile, so they read as part of a page's white header block (e.g. Clients Show) instead of sitting on the plain app background. */
    breadcrumbsSurface?: boolean;
}>();
</script>

<template>
    <main class="flex-1 overflow-auto p-4 md:p-8">
        <div
            v-if="breadcrumbs?.length"
            :class="
                breadcrumbsSurface
                    ? '-mx-4 -mt-4 bg-surface px-4 py-3 sm:mx-0 sm:mt-0 sm:mb-3 sm:bg-transparent sm:px-0 sm:py-0'
                    : 'mb-3'
            "
        >
            <Breadcrumbs :breadcrumbs="breadcrumbs" />
        </div>
        <slot />
    </main>
</template>
