<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import type { Paginated } from '@/types';
import type { PolicyResource } from '@/types/policy';
import type { AgentResource } from '../Agents/partials/agent';
import AgentDetailShell from '../Agents/partials/AgentDetailShell.vue';
import PoliciesTable from '../Policies/partials/PoliciesTable.vue';

defineProps<{
    agent: AgentResource;
    policiesCount: number;
    policies: Paginated<PolicyResource>;
}>();

// The Clients tab is wired by a separate issue; this page only owns Policies.
const clientsCount = 0;
</script>

<template>
    <AgentDetailShell
        :agent="agent"
        :clients-count="clientsCount"
        :policies-count="policiesCount"
    >
        <Head :title="`${agent.full_name} · Policies`" />

        <div class="mt-6 flex-1">
            <PoliciesTable
                v-if="policies.data.length > 0"
                :policies="policies"
            />
            <Card v-else>
                <CardContent
                    class="py-16 text-center text-[13px] text-tertiary"
                >
                    No policies for this agent yet.
                </CardContent>
            </Card>
        </div>
    </AgentDetailShell>
</template>
