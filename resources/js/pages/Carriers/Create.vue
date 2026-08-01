<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index as carriersIndex } from '@/routes/carriers';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Carriers',
                href: carriersIndex(),
            },
            {
                title: 'Create',
            },
        ],
    },
});
</script>

<template>
    <Head title="Create Carrier" />

    <div class="flex flex-1 flex-col gap-4">
        <pre>{{ $page.props }}</pre>
    </div>
</template>
