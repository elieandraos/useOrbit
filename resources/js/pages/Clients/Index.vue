<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download, Filter, Plus } from '@lucide/vue';
import { computed } from 'vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import Button from '@/components/ui/button/Button.vue';
import { create as clientsCreate } from '@/routes/clients';
import EmptyState from './partials/EmptyState.vue';

const props = defineProps<{
    clients: {
        data: unknown[];
    };
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

        <EmptyState v-if="!hasClients" />
    </div>
</template>
