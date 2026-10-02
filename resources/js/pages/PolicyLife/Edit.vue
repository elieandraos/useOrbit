<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import {
    show as policiesLifeShow,
    update as policiesLifeUpdate,
} from '@/routes/policies/life';
import type { PolicyCurrencyOption } from '@/types/policy';
import type { PolicyLifeResource } from './partials/policy';
import PolicyLifeForm from './partials/PolicyLifeForm.vue';

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
    policy: PolicyLifeResource;
    clients: EntityOption[];
    carriers: EntityOption[];
    agents: EntityOption[];
    subclasses: string[];
    types: Option[];
    statuses: Option[];
    sources: Option[];
    currencies: PolicyCurrencyOption[];
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Policies',
            href: policiesIndex(),
        },
        {
            title: props.policy.policy_number,
            href: policiesLifeShow(props.policy.slug),
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
            subtitle="Update coverage, parties, and the Life-specific details. The policy class can't be changed."
            :divider="false"
        />

        <PolicyLifeForm
            :policy="policy"
            :clients="clients"
            :carriers="carriers"
            :agents="agents"
            :subclasses="subclasses"
            :types="types"
            :statuses="statuses"
            :sources="sources"
            :currencies="currencies"
            :route="policiesLifeUpdate.form({ policy: policy.slug })"
            submit-label="Save changes"
        />
    </div>
</template>
