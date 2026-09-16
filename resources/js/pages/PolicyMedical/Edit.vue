<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import {
    show as policiesMedicalShow,
    update as policiesMedicalUpdate,
} from '@/routes/policies/medical';
import PolicyMedicalForm from './partials/PolicyMedicalForm.vue';

interface Option {
    label: string;
    value: string;
}

interface EntityOption {
    id: number;
    full_name?: string;
    name?: string;
}

interface InsuredValues {
    id?: number;
    full_name: string;
    relationship: string;
    date_of_birth: string;
    gender: string | null;
    medical_notes: string | null;
}

interface PolicyMedicalResource {
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
        coverage_scope: string;
        class_tier: string;
        co_insurance: boolean;
        co_insurance_share: string | null;
        guaranteed_renewable: boolean;
        insured_full_name: string | null;
        insured_date_of_birth: string | null;
        insured_gender: string | null;
        insured_smoker: boolean | null;
        insured_medical_history: string | null;
    };
    insureds: InsuredValues[];
}

const props = defineProps<{
    policy: PolicyMedicalResource;
    clients: EntityOption[];
    carriers: EntityOption[];
    agents: EntityOption[];
    types: Option[];
    statuses: Option[];
    sources: Option[];
    coverageScopes: Option[];
    classTiers: Option[];
    genders: Option[];
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Policies',
            href: policiesIndex(),
        },
        {
            title: props.policy.policy_number,
            href: policiesMedicalShow(props.policy.slug),
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
            subtitle="Update coverage, parties, and the Medical-specific details. The policy class can't be changed."
            :divider="false"
        />

        <PolicyMedicalForm
            :policy="policy"
            :clients="clients"
            :carriers="carriers"
            :agents="agents"
            :types="types"
            :statuses="statuses"
            :sources="sources"
            :coverage-scopes="coverageScopes"
            :class-tiers="classTiers"
            :genders="genders"
            :route="policiesMedicalUpdate.form({ policy: policy.slug })"
            submit-label="Save changes"
        />
    </div>
</template>
