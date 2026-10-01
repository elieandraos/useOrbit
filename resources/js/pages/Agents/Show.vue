<script setup lang="ts">
import type { PolicyResource } from '@/types/policy';
import type { AgentResource } from './partials/agent';
import AgentDetailShell from './partials/AgentDetailShell.vue';
import ContactCard from './partials/ContactCard.vue';
import PersonalInformationCard from './partials/PersonalInformationCard.vue';
import PoliciesRenewalCard from './partials/PoliciesRenewalCard.vue';
import QuickStatsCard from './partials/QuickStatsCard.vue';

defineProps<{
    agent: AgentResource;
    policiesCount: number;
    clientsCount: number;
    renewingPolicies: PolicyResource[];
}>();
</script>

<template>
    <AgentDetailShell :agent="agent" :policies-count="policiesCount">
        <div
            class="grid grid-cols-1 items-start gap-5 pt-6 lg:grid-cols-[360px_1fr]"
        >
            <!-- Left column -->
            <div class="flex flex-col gap-4">
                <PersonalInformationCard :agent="agent" />
                <ContactCard :agent="agent" />
            </div>

            <!-- Right column -->
            <div class="flex flex-col gap-4">
                <QuickStatsCard
                    :agent="agent"
                    :policies-count="policiesCount"
                    :clients-count="clientsCount"
                />
                <PoliciesRenewalCard :policies="renewingPolicies" />
            </div>
        </div>
    </AgentDetailShell>
</template>
