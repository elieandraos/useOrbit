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
                title: 'Create',
            },
        ],
    },
});
</script>

<template>
    <Head title="Create Agent" />

    <div class="flex flex-1 flex-col gap-4">
        <pre>{{ $page.props }}</pre>
    </div>
</template>
