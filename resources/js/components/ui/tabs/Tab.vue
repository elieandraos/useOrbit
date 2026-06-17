<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { useCurrentUrl } from '@/composables/useCurrentUrl';

defineProps<{
    href: NonNullable<InertiaLinkProps['href']>;
    title?: string;
}>();

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <Link
        :href="href"
        class="border-b-2 px-3 py-2 text-sm font-medium transition-colors"
        :class="
            isCurrentOrParentUrl(href)
                ? 'border-accent text-accent'
                : 'border-transparent text-secondary hover:text-primary'
        "
    >
        <slot>{{ title }}</slot>
    </Link>
</template>
