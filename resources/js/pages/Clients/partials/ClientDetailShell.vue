<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Tab, Tabs } from '@/components/ui/tabs';
import { index as clientsIndex, show as clientsShow } from '@/routes/clients';
import type { ClientResource } from './client';
import ClientShowHeader from './ClientShowHeader.vue';

const props = defineProps<{
    client: ClientResource;
    policiesCount: number;
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Clients',
            href: clientsIndex(),
        },
        {
            title: props.client.full_name,
        },
    ],
    breadcrumbsSurface: true,
});
</script>

<template>
    <Head :title="client.full_name" />

    <div class="flex flex-1 flex-col">
        <ClientShowHeader :client="client" :policies-count="policiesCount" />

        <div
            class="-mx-4 overflow-x-auto border-b border-border-subtle bg-surface px-4 sm:mx-0 sm:mt-6 sm:overflow-visible sm:border-0 sm:bg-transparent sm:px-0"
        >
            <Tabs class="min-w-max">
                <Tab :href="clientsShow(client.slug)">Overview</Tab>
                <Tab href="#">Policies</Tab>
                <Tab href="#">Documents</Tab>
                <Tab href="#">Notes</Tab>
            </Tabs>
        </div>

        <slot />
    </div>
</template>
