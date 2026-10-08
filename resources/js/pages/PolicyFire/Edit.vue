<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import {
    show as policiesFireShow,
    update as policiesFireUpdate,
} from '@/routes/policies/fire';
import type { PolicyCurrencyOption } from '@/types/policy';
import type { PolicyFireResource } from './partials/policy';
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

const props = defineProps<{
    policy: PolicyFireResource;
    carriers: EntityOption[];
    agents: EntityOption[];
    subclasses: string[];
    types: Option[];
    sources: Option[];
    currencies: PolicyCurrencyOption[];
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
            :carriers="carriers"
            :agents="agents"
            :subclasses="subclasses"
            :types="types"
            :sources="sources"
            :currencies="currencies"
            :countries="countries"
            :route="policiesFireUpdate.form({ policy: policy.slug })"
            submit-label="Save changes"
        />
    </div>
</template>
