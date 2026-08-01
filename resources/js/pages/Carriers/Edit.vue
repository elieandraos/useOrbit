<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { index as carriersIndex, show as carriersShow } from '@/routes/carriers';

const props = defineProps<{
    carrier: { slug: string };
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Carriers',
            href: carriersIndex(),
        },
        {
            title: 'Carrier',
            href: carriersShow({ carrier: props.carrier.slug }),
        },
        {
            title: 'Edit',
        },
    ],
});
</script>

<template>
    <Head title="Edit Carrier" />

    <div class="flex flex-1 flex-col gap-4">
        <pre>{{ carrier }}</pre>
    </div>
</template>
