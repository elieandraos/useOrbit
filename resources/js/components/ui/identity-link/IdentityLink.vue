<script setup lang="ts">
import type { LinkComponentBaseProps } from '@inertiajs/core';
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: LinkComponentBaseProps['href'];
}>();
</script>

<template>
    <!-- Links a related party (client, insurance company, agent) to its Show page. Never nest inside an already-clickable row. -->
    <Link
        :href="href"
        data-slot="identity-link"
        class="group/identity inline-flex min-w-0 items-center gap-2 rounded-sm font-medium text-accent transition-colors hover:text-accent-hover focus-visible:ring-2 focus-visible:ring-accent-ring focus-visible:outline-none"
    >
        <slot name="leading" />
        <span
            class="truncate underline-offset-2 group-hover/identity:underline"
        >
            <slot />
        </span>
    </Link>
</template>
