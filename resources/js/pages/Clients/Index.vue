<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download, Filter, Plus } from '@lucide/vue';
import { computed } from 'vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { create as clientsCreate } from '@/routes/clients';
import type { Paginated } from '@/types';
import type { ClientResource } from './partials/client';
import ClientsTable from './partials/ClientsTable.vue';
import EmptyState from './partials/EmptyState.vue';

const props = defineProps<{
    clients: Paginated<ClientResource>;
}>();

const hasClients = computed(() => props.clients.data.length > 0);
</script>

<template>
    <Head title="Clients" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="Clients"
            subtitle="Manage individual and corporate insurance clients"
        >
            <template v-if="hasClients" #meta>
                <div class="flex flex-wrap items-center gap-2.5">
                    <Badge tone="neutral"
                        >{{ clients.meta.total }} clients</Badge
                    >
                    <span class="text-xs text-tertiary">·</span>
                    <span class="text-xs text-tertiary"
                        >Sorted by enrollment date · newest first</span
                    >
                </div>
            </template>

            <template #actions>
                <template v-if="hasClients">
                    <Button variant="secondary" size="md">
                        <template #leading><Filter /></template>
                        Filters
                    </Button>
                    <Button variant="secondary" size="md">
                        <template #leading><Download /></template>
                        Export
                    </Button>
                    <Link :href="clientsCreate().url">
                        <Button variant="primary" size="md">
                            <template #leading><Plus /></template>
                            Add New Client
                        </Button>
                    </Link>
                </template>
            </template>
        </PageHeader>

        <ClientsTable v-if="hasClients" class="mt-5" :clients="clients" />
        <EmptyState v-else />
    </div>
</template>
