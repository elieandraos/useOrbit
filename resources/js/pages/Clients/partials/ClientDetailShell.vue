<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Tab, Tabs } from '@/components/ui/tabs';
import { index as clientsIndex, show as clientsShow } from '@/routes/clients';
import { index as clientsDocumentsIndex } from '@/routes/clients/documents';
import { index as clientsNotesIndex } from '@/routes/clients/notes';
import { index as clientsPoliciesIndex } from '@/routes/clients/policies';
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
                <Tab :href="clientsPoliciesIndex(client.slug)">Policies</Tab>
                <Tab :href="clientsDocumentsIndex(client.slug)">Documents</Tab>
                <Tab :href="clientsNotesIndex(client.slug)">Notes</Tab>
            </Tabs>
        </div>

        <slot />
    </div>
</template>
