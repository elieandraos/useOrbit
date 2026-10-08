<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import { store as policiesFireStore } from '@/routes/policies/fire';
import type { PolicyCurrencyOption, PolicyEntry } from '@/types/policy';
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
    countries: CountryOption[];
    defaultCountryId: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Policies',
                href: policiesIndex(),
            },
            {
                title: 'New Fire policy',
            },
        ],
    },
});
</script>

<template>
    <Head title="New Fire policy" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="New Fire policy"
            subtitle="Coverage, parties, and the Fire-specific details for this policy."
            :divider="false"
        />

        <PolicyFireForm
            :clients="clients"
            :carriers="carriers"
            :agents="agents"
            :subclasses="subclasses"
            :types="types"
            :sources="sources"
            :currencies="currencies"
            :default-currency-id="defaultCurrencyId"
            :countries="countries"
            :default-country-id="defaultCountryId"
            :entry="entry"
            :route="policiesFireStore.form()"
            submit-label="Create policy"
        />
    </div>
</template>
