<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed } from 'vue';
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
        <div
            class="flex items-start justify-between gap-4 border-b border-border-subtle pb-5"
        >
            <div class="flex flex-col gap-0.5">
                <h1 class="text-xl font-semibold text-primary">Clients</h1>
                <p class="text-sm text-tertiary">
                    Manage individual and corporate insurance clients
                </p>
            </div>
            <Link v-if="hasClients" :href="clientsCreate().url">
                <Button variant="primary" size="md">
                    <template #leading><Plus /></template>
                    Add New Client
                </Button>
            </Link>
        </div>

        <EmptyState v-if="!hasClients" />
    </div>
</template>
