<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import { store as policiesTravelStore } from '@/routes/policies/travel';
import type { PolicyCurrencyOption, PolicyEntry } from '@/types/policy';
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

defineProps<{
    carriers: EntityOption[];
    agents: EntityOption[];
    subclasses: string[];
    coverageTiers: Option[];
    types: Option[];
    sources: Option[];
    currencies: PolicyCurrencyOption[];
    defaultCurrencyId: number | null;
    entry: PolicyEntry;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Policies',
                href: policiesIndex(),
            },
            {
                title: 'New Travel policy',
            },
        ],
    },
});
</script>

<template>
    <Head title="New Travel policy" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="New Travel policy"
            subtitle="Coverage, parties, and the Travel-specific details for this policy."
            :divider="false"
        />

        <PolicyTravelForm
            :carriers="carriers"
            :agents="agents"
            :subclasses="subclasses"
            :coverage-tiers="coverageTiers"
            :types="types"
            :sources="sources"
            :currencies="currencies"
            :default-currency-id="defaultCurrencyId"
            :entry="entry"
            :route="policiesTravelStore.form()"
            submit-label="Create policy"
        />
    </div>
</template>
