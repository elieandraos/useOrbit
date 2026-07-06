<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Tab, Tabs } from '@/components/ui/tabs';
import { index as clientsIndex, show as clientsShow } from '@/routes/clients';
import type { ClientResource } from './partials/client';
import ClientHeader from './partials/ClientHeader.vue';
import ClientPoliciesCard from './partials/ClientPoliciesCard.vue';
import ContactCard from './partials/ContactCard.vue';
import EmergencyContactCard from './partials/EmergencyContactCard.vue';
import EnrollmentCard from './partials/EnrollmentCard.vue';
import NextRenewalCard from './partials/NextRenewalCard.vue';
import PersonalInformationCard from './partials/PersonalInformationCard.vue';
import QuickStatsCard from './partials/QuickStatsCard.vue';
import RecentActivitiesCard from './partials/RecentActivitiesCard.vue';

const props = defineProps<{
    client: ClientResource;
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Clients',
            href: clientsIndex(),
        },
        {
            title: props.client.full_name,
        },
    ],
});

const policiesCount = 0;
</script>

<template>
    <Head :title="client.full_name" />

    <div class="flex flex-1 flex-col">
        <ClientHeader :client="client" :policies-count="policiesCount" />

        <Tabs class="mt-6">
            <Tab :href="clientsShow(client.slug)">Overview</Tab>
            <Tab href="#">Policies</Tab>
            <Tab href="#">Documents</Tab>
            <Tab href="#">Notes</Tab>
        </Tabs>

        <div class="grid grid-cols-[360px_1fr] items-start gap-5 pt-6">
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

                <div class="grid grid-cols-2 gap-4">
                    <QuickStatsCard />
                    <NextRenewalCard />
                </div>

                <RecentActivitiesCard />
            </div>
        </div>
    </div>
</template>
