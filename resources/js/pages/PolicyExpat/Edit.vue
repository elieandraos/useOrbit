<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import {
    show as policiesExpatShow,
    update as policiesExpatUpdate,
} from '@/routes/policies/expat';
import PolicyExpatForm from './partials/PolicyExpatForm.vue';

interface Option {
    label: string;
    value: string;
}

interface EntityOption {
    id: number;
    full_name?: string;
    name?: string;
}

interface CountryOption {
    id: number;
    name: string;
}

interface PolicyExpatResource {
    slug: string;
    policy_number: string;
    subclass: string;
    type: string;
    client_id: number;
    carrier_id: number;
    agent_id: number | null;
    effective_date: string;
    expiry_date: string;
    premium_amount: string;
    discount_amount: string;
    status: string;
    source: string;
    details: {
        coverage_zone: string;
        travel_scope: string | null;
        full_name: string;
        gender: string;
        nationality: string;
        date_of_birth: string;
        phone: string;
        country_id: number | null;
        visa_expiry_date: string | null;
    };
}

const props = defineProps<{
    policy: PolicyExpatResource;
    clients: EntityOption[];
    carriers: EntityOption[];
    agents: EntityOption[];
    types: Option[];
    statuses: Option[];
    sources: Option[];
    coverageZones: Option[];
    genders: Option[];
    countries: CountryOption[];
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Policies',
            href: policiesIndex(),
        },
        {
            title: props.policy.policy_number,
            href: policiesExpatShow(props.policy.slug),
        },
        {
            title: 'Edit',
        },
    ],
});
</script>

<template>
    <Head :title="`Edit ${policy.policy_number}`" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            :title="`Edit ${policy.policy_number}`"
            subtitle="Update coverage, parties, and the Expat-specific details. The policy class can't be changed."
            :divider="false"
        />

        <PolicyExpatForm
            :policy="policy"
            :clients="clients"
            :carriers="carriers"
            :agents="agents"
            :types="types"
            :statuses="statuses"
            :sources="sources"
            :coverage-zones="coverageZones"
            :genders="genders"
            :countries="countries"
            :route="policiesExpatUpdate.form({ policy: policy.slug })"
            submit-label="Save changes"
        />
    </div>
</template>
