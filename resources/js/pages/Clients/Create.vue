<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index as clientsIndex } from '@/routes/clients';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Clients',
                href: clientsIndex(),
            },
            {
                title: 'Create',
            },
        ],
    },
});
</script>

<template>
    <Head title="Create Client" />

    <div class="flex flex-1 flex-col gap-4">
        <pre>{{ $page.props }}</pre>
    </div>
</template>
