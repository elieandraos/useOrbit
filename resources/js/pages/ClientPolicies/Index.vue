<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Paginated } from '@/types';
import type { PolicyResource } from '@/types/policy';
import type { ClientResource } from '../Clients/partials/client';
import ClientDetailShell from '../Clients/partials/ClientDetailShell.vue';
import EmptyState from '../Policies/partials/EmptyState.vue';
import PoliciesTable from '../Policies/partials/PoliciesTable.vue';

const props = defineProps<{
    client: ClientResource;
    policies: Paginated<PolicyResource>;
}>();

const hasPolicies = computed(() => props.policies.data.length > 0);
</script>

<template>
    <ClientDetailShell :client="client" :policies-count="policies.meta.total">
        <Head :title="`${client.full_name} · Policies`" />

        <div v-if="hasPolicies" class="mt-6 flex-1">
            <PoliciesTable :policies="policies" />
        </div>
        <EmptyState v-else />
    </ClientDetailShell>
</template>
