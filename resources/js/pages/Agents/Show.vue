<script setup lang="ts">
import type { AgentResource } from './partials/agent';
import AgentDetailShell from './partials/AgentDetailShell.vue';
import ContactCard from './partials/ContactCard.vue';
import PersonalInformationCard from './partials/PersonalInformationCard.vue';
import PoliciesRenewalCard from './partials/PoliciesRenewalCard.vue';
import QuickStatsCard from './partials/QuickStatsCard.vue';
import TopClientsCard from './partials/TopClientsCard.vue';

defineProps<{
    agent: AgentResource;
}>();

const clientsCount = 0;
const policiesCount = 0;
</script>

<template>
    <AgentDetailShell
        :agent="agent"
        :clients-count="clientsCount"
        :policies-count="policiesCount"
    >
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
                <QuickStatsCard :agent="agent" />
                <PoliciesRenewalCard />
                <TopClientsCard />
            </div>
        </div>
    </AgentDetailShell>
</template>
