<script setup lang="ts">
import type { ClientResource } from './partials/client';
import ClientDetailShell from './partials/ClientDetailShell.vue';
import ClientPoliciesCard from './partials/ClientPoliciesCard.vue';
import ContactCard from './partials/ContactCard.vue';
import EmergencyContactCard from './partials/EmergencyContactCard.vue';
import EnrollmentCard from './partials/EnrollmentCard.vue';
import NextRenewalCard from './partials/NextRenewalCard.vue';
import PersonalInformationCard from './partials/PersonalInformationCard.vue';
import QuickStatsCard from './partials/QuickStatsCard.vue';
import RecentActivitiesCard from './partials/RecentActivitiesCard.vue';

defineProps<{
    client: ClientResource;
}>();

const policiesCount = 0;
</script>

<template>
    <ClientDetailShell :client="client" :policies-count="policiesCount">
        <div
            class="grid grid-cols-1 items-start gap-5 pt-6 lg:grid-cols-[360px_1fr]"
        >
            <!-- Left column -->
            <div class="flex flex-col gap-4">
                <PersonalInformationCard :client="client" />
                <ContactCard :client="client" />
                <EnrollmentCard :client="client" />
                <EmergencyContactCard :client="client" />
            </div>

            <!-- Right column -->
            <div class="flex flex-col gap-4">
                <ClientPoliciesCard />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <QuickStatsCard />
                    <NextRenewalCard />
                </div>

                <RecentActivitiesCard />
            </div>
        </div>
    </ClientDetailShell>
</template>
