<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import {
    show as policiesAutomotiveShow,
    update as policiesAutomotiveUpdate,
} from '@/routes/policies/automotive';
import PolicyAutomotiveForm from './partials/PolicyAutomotiveForm.vue';

interface Option {
    label: string;
    value: string;
}

interface EntityOption {
    id: number;
    full_name?: string;
    name?: string;
}

interface PolicyAutomotiveResource {
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
        plate_number: string;
        make: string;
        model: string;
        year: number;
        vin: string | null;
        color: string | null;
        valuation_amount: string | null;
        valuation_source: string | null;
    };
}

const props = defineProps<{
    policy: PolicyAutomotiveResource;
    clients: EntityOption[];
    carriers: EntityOption[];
    agents: EntityOption[];
    types: Option[];
    statuses: Option[];
    sources: Option[];
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Policies',
            href: policiesIndex(),
        },
        {
            title: props.policy.policy_number,
            href: policiesAutomotiveShow(props.policy.slug),
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
            subtitle="Update coverage, parties, and the Automotive-specific details. The policy class can't be changed."
            :divider="false"
        />

        <PolicyAutomotiveForm
            :policy="policy"
            :clients="clients"
            :carriers="carriers"
            :agents="agents"
            :types="types"
            :statuses="statuses"
            :sources="sources"
            :route="policiesAutomotiveUpdate.form({ policy: policy.slug })"
            submit-label="Save changes"
        />
    </div>
</template>
