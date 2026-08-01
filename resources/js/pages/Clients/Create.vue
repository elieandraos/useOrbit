<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as clientsIndex, store as clientsStore } from '@/routes/clients';
import ClientForm from './partials/ClientForm.vue';

defineProps<{
    countries: { id: number; name: string }[];
    genders: { label: string; value: string }[];
    leadSources: { label: string; value: string }[];
    emergencyContactRelationships: { label: string; value: string }[];
    clientTypes: { label: string; value: string }[];
    defaultCountryId: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Clients',
                href: clientsIndex(),
            },
            {
                title: 'Create',
            },
        ],
    },
});
</script>

<template>
    <Head title="Add new client" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="Add new client"
            subtitle="Capture personal details, contact info, and how you met. You can add policies after creating the client."
            :divider="false"
        />

        <ClientForm
            :countries="countries"
            :genders="genders"
            :lead-sources="leadSources"
            :emergency-contact-relationships="emergencyContactRelationships"
            :client-types="clientTypes"
            :default-country-id="defaultCountryId"
            :route="clientsStore.form()"
            submit-label="Create client"
        />
    </div>
</template>
