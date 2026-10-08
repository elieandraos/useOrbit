<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import { store as policiesExpatStore } from '@/routes/policies/expat';
import type { PolicyCurrencyOption, PolicyEntry } from '@/types/policy';
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

defineProps<{
    carriers: EntityOption[];
    agents: EntityOption[];
    subclasses: string[];
    types: Option[];
    sources: Option[];
    currencies: PolicyCurrencyOption[];
    defaultCurrencyId: number | null;
    entry: PolicyEntry;
    coverageZones: Option[];
    genders: Option[];
    countries: CountryOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Policies',
                href: policiesIndex(),
            },
            {
                title: 'New Expat policy',
            },
        ],
    },
});
</script>

<template>
    <Head title="New Expat policy" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="New Expat policy"
            subtitle="Coverage, parties, and the Expat-specific details for this policy."
            :divider="false"
        />

        <PolicyExpatForm
            :carriers="carriers"
            :agents="agents"
            :subclasses="subclasses"
            :types="types"
            :sources="sources"
            :currencies="currencies"
            :default-currency-id="defaultCurrencyId"
            :coverage-zones="coverageZones"
            :genders="genders"
            :countries="countries"
            :entry="entry"
            :route="policiesExpatStore.form()"
            submit-label="Create policy"
        />
    </div>
</template>
