<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-4 p-8">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="aspect-video rounded-[12px] border border-border bg-surface"
            />
            <div
                class="aspect-video rounded-[12px] border border-border bg-surface"
            />
            <div
                class="aspect-video rounded-[12px] border border-border bg-surface"
            />
        </div>
        <div
            class="min-h-[60vh] flex-1 rounded-[12px] border border-border bg-surface"
        />
    </div>
</template>
