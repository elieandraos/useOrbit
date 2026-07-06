<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Button from '@/components/ui/button/Button.vue';
import type { PaginationMeta } from '@/types';

const props = defineProps<{
    meta: PaginationMeta;
    itemLabel: string;
}>();

const previous = () => props.meta.links[0];
const next = () => props.meta.links[props.meta.links.length - 1];
const pages = () => props.meta.links.slice(1, -1);

function goTo(url: string | null) {
    if (!url) {
        return;
    }

    router.visit(url, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <div class="flex items-center justify-between border-t border-border-subtle px-4 py-3 text-xs text-secondary">
        <div>
            Showing
            <span class="font-medium text-primary">{{ meta.from }}–{{ meta.to }}</span>
            of
            <span class="font-medium text-primary">{{ meta.total }}</span>
            {{ itemLabel }}
        </div>

        <div class="flex items-center gap-1.5">
            <Button variant="ghost" size="sm" :disabled="!previous().url" @click="goTo(previous().url)">Previous</Button>

            <div class="inline-flex items-center gap-0.5 rounded-md bg-sunken p-0.5">
                <button
                    v-for="link in pages()"
                    :key="link.label"
                    type="button"
                    class="min-w-[26px] cursor-pointer rounded-[6px] px-1.5 py-1 text-xs font-medium transition-colors"
                    :class="link.active ? 'bg-surface text-primary shadow-card' : 'text-secondary hover:text-primary'"
                    @click="goTo(link.url)"
                >
                    {{ link.label }}
                </button>
            </div>

            <Button variant="ghost" size="sm" :disabled="!next().url" @click="goTo(next().url)">Next</Button>
        </div>
    </div>
</template>
