<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import {
    show as policiesExpatShow,
    update as policiesExpatUpdate,
} from '@/routes/policies/expat';
import type { PolicyExpatResource } from './partials/policy';
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

const props = defineProps<{
    policy: PolicyExpatResource;
    clients: EntityOption[];
    carriers: EntityOption[];
    agents: EntityOption[];
    subclasses: string[];
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
            :subclasses="subclasses"
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
