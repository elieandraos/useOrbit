<script setup lang="ts">
import type { PolicyResource } from '@/types/policy';
import type { ClientResource } from './partials/client';
import ClientDetailShell from './partials/ClientDetailShell.vue';
import ClientPoliciesCard from './partials/ClientPoliciesCard.vue';
import CompanyInformationCard from './partials/CompanyInformationCard.vue';
import ContactCard from './partials/ContactCard.vue';
import EmergencyContactCard from './partials/EmergencyContactCard.vue';
import EnrollmentCard from './partials/EnrollmentCard.vue';
import PersonalInformationCard from './partials/PersonalInformationCard.vue';

defineProps<{
    client: ClientResource;
    policiesCount: number;
    recentPolicies: PolicyResource[];
}>();
</script>

<template>
    <ClientDetailShell :client="client" :policies-count="policiesCount">
        <div
            class="grid grid-cols-1 items-start gap-5 pt-6 lg:grid-cols-[360px_1fr]"
        >
            <!-- Left column -->
            <div class="flex flex-col gap-4">
                <template v-if="client.client_type === 'company'">
                    <CompanyInformationCard :client="client" />
                </template>
                <template v-else>
                    <PersonalInformationCard :client="client" />
                    <ContactCard :client="client" />
                </template>
                <EnrollmentCard :client="client" />
                <EmergencyContactCard
                    v-if="client.client_type !== 'company'"
                    :client="client"
                />
            </div>

            <!-- Right column -->
            <div class="flex flex-col gap-4">
                <ClientPoliciesCard
                    :client="client"
                    :policies="recentPolicies"
                />
            </div>
        </div>
    </ClientDetailShell>
</template>
