<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import type { Paginated } from '@/types';
import type { AgentResource } from '../Agents/partials/agent';
import AgentDetailShell from '../Agents/partials/AgentDetailShell.vue';
import type { ClientResource } from '../Clients/partials/client';
import AgentClientsTable from './partials/AgentClientsTable.vue';

defineProps<{
    agent: AgentResource;
    policiesCount: number;
    clients: Paginated<ClientResource>;
}>();
</script>

<template>
    <AgentDetailShell :agent="agent" :policies-count="policiesCount">
        <Head :title="`${agent.full_name} · Clients`" />

        <div class="mt-6 flex-1">
            <AgentClientsTable
                v-if="clients.data.length > 0"
                :clients="clients"
            />
            <Card v-else>
                <CardContent
                    class="py-16 text-center text-[13px] text-tertiary"
                >
                    No clients for this agent yet.
                </CardContent>
            </Card>
        </div>
    </AgentDetailShell>
</template>
