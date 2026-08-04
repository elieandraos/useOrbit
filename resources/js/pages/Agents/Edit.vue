<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index as agentsIndex } from '@/routes/agents';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Agents',
                href: agentsIndex(),
            },
            {
                title: 'Edit',
            },
        ],
    },
});

defineProps<{
    agent: object;
}>();
</script>

<template>
    <Head title="Edit Agent" />

    <div class="flex flex-1 flex-col gap-4">
        <pre>{{ agent }}</pre>
    </div>
</template>
