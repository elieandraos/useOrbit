<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import {
    show as policiesFireShow,
    update as policiesFireUpdate,
} from '@/routes/policies/fire';
import PolicyFireForm from './partials/PolicyFireForm.vue';

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

interface PolicyFireResource {
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
        property_type: string;
        floor_area: number;
        year_built: number | null;
        street: string;
        building_floor: string | null;
        city: string;
        state_id: number | null;
        state_name: string | null;
        country_id: number | null;
        sum_insured: string;
    };
}

const props = defineProps<{
    policy: PolicyFireResource;
    clients: EntityOption[];
    carriers: EntityOption[];
    agents: EntityOption[];
    types: Option[];
    statuses: Option[];
    sources: Option[];
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
            href: policiesFireShow(props.policy.slug),
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
            subtitle="Update coverage, parties, and the Fire-specific details. The policy class can't be changed."
            :divider="false"
        />

        <PolicyFireForm
            :policy="policy"
            :clients="clients"
            :carriers="carriers"
            :agents="agents"
            :types="types"
            :statuses="statuses"
            :sources="sources"
            :countries="countries"
            :route="policiesFireUpdate.form({ policy: policy.slug })"
            submit-label="Save changes"
        />
    </div>
</template>
