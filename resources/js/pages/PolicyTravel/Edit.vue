<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import {
    show as policiesTravelShow,
    update as policiesTravelUpdate,
} from '@/routes/policies/travel';
import type { PolicyCurrencyOption } from '@/types/policy';
import type { PolicyTravelResource } from './partials/policy';
import PolicyTravelForm from './partials/PolicyTravelForm.vue';

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
    policy: PolicyTravelResource;
    carriers: EntityOption[];
    agents: EntityOption[];
    subclasses: string[];
    coverageTiers: Option[];
    types: Option[];
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
            href: policiesTravelShow(props.policy.slug),
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
            subtitle="Update coverage, parties, and the Travel-specific details. The policy class can't be changed."
            :divider="false"
        />

        <PolicyTravelForm
            :policy="policy"
            :carriers="carriers"
            :agents="agents"
            :subclasses="subclasses"
            :coverage-tiers="coverageTiers"
            :types="types"
            :sources="sources"
            :currencies="currencies"
            :route="policiesTravelUpdate.form({ policy: policy.slug })"
            submit-label="Save changes"
        />
    </div>
</template>
