<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import { store as policiesMedicalStore } from '@/routes/policies/medical';
import type { PolicyCurrencyOption, PolicyEntry } from '@/types/policy';
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

defineProps<{
    clients: EntityOption[];
    carriers: EntityOption[];
    agents: EntityOption[];
    subclasses: string[];
    types: Option[];
    sources: Option[];
    currencies: PolicyCurrencyOption[];
    defaultCurrencyId: number | null;
    entry: PolicyEntry;
    coverageScopes: Option[];
    classTiers: Option[];
    genders: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Policies',
                href: policiesIndex(),
            },
            {
                title: 'New Medical policy',
            },
        ],
    },
});
</script>

<template>
    <Head title="New Medical policy" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="New Medical policy"
            subtitle="Coverage, parties, and the Medical-specific details for this policy."
            :divider="false"
        />

        <PolicyMedicalForm
            :clients="clients"
            :carriers="carriers"
            :agents="agents"
            :subclasses="subclasses"
            :types="types"
            :sources="sources"
            :currencies="currencies"
            :default-currency-id="defaultCurrencyId"
            :coverage-scopes="coverageScopes"
            :class-tiers="classTiers"
            :genders="genders"
            :entry="entry"
            :route="policiesMedicalStore.form()"
            submit-label="Create policy"
        />
    </div>
</template>
