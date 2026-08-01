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
        ],
    },
});

defineProps<{
    carrier: object;
}>();
</script>

<template>
    <Head title="Carrier" />

    <div class="flex flex-1 flex-col gap-4">
        <pre>{{ carrier }}</pre>
    </div>
</template>
