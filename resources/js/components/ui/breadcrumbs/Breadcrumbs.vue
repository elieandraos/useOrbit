<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    breadcrumbs: BreadcrumbItem[];
}>();
</script>

<template>
    <nav aria-label="breadcrumb">
        <ol class="flex flex-wrap items-center gap-1.5 text-sm text-secondary">
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <li class="inline-flex items-center gap-1.5">
                    <span
                        v-if="index === breadcrumbs.length - 1"
                        aria-current="page"
                        class="text-primary"
                    >
                        {{ item.title }}
                    </span>
                    <Link
                        v-else
                        :href="item.href"
                        class="transition-colors hover:text-primary"
                    >
                        {{ item.title }}
                    </Link>
                </li>
                <li
                    v-if="index !== breadcrumbs.length - 1"
                    role="presentation"
                    aria-hidden="true"
                >
                    <ChevronRight class="size-3.5" />
                </li>
            </template>
        </ol>
    </nav>
</template>
