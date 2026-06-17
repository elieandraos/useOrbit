<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { inject } from 'vue';

defineProps<{
    href?: NonNullable<InertiaLinkProps['href']>;
    method?: InertiaLinkProps['method'];
    as?: string;
    danger?: boolean;
}>();

const closeMenu = inject<() => void>('dropMenuClose', () => {});
</script>

<template>
    <Link
        v-if="href"
        :href="href"
        :method="method"
        :as="as"
        class="flex w-full items-center gap-2 rounded-[6px] px-2 py-1.5 text-sm transition-colors hover:bg-sunken"
        :class="danger ? 'text-danger' : 'text-primary'"
        v-bind="$attrs"
        @click="closeMenu"
    >
        <slot name="leading" />
        <slot />
    </Link>
    <button
        v-else
        type="button"
        class="flex w-full items-center gap-2 rounded-[6px] px-2 py-1.5 text-left text-sm transition-colors hover:bg-sunken"
        :class="danger ? 'text-danger' : 'text-primary'"
        v-bind="$attrs"
        @click="closeMenu"
    >
        <slot name="leading" />
        <slot />
    </button>
</template>
