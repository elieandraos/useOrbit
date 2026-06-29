<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { index as clientsIndex, show as clientsShow } from '@/routes/clients';

const props = defineProps<{
    client: { slug: string };
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Clients',
            href: clientsIndex(),
        },
        {
            title: 'Client',
            href: clientsShow({ client: props.client.slug }),
        },
        {
            title: 'Edit',
        },
    ],
});
</script>

<template>
    <Head title="Edit Client" />

    <div class="flex flex-1 flex-col gap-4">
        <pre>{{ client }}</pre>
    </div>
</template>
