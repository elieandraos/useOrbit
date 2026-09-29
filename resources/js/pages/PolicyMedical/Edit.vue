<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import {
    show as policiesMedicalShow,
    update as policiesMedicalUpdate,
} from '@/routes/policies/medical';
import type { PolicyMedicalResource } from './partials/policy';
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

const props = defineProps<{
    policy: PolicyMedicalResource;
    clients: EntityOption[];
    carriers: EntityOption[];
    agents: EntityOption[];
    subclasses: string[];
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
            :subclasses="subclasses"
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
