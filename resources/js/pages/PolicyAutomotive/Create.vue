<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import { store as policiesAutomotiveStore } from '@/routes/policies/automotive';
import type { PolicyCurrencyOption, PolicyEntry } from '@/types/policy';
import PolicyAutomotiveForm from './partials/PolicyAutomotiveForm.vue';

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
                title: 'New Automotive policy',
            },
        ],
    },
});
</script>

<template>
    <Head title="New Automotive policy" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="New Automotive policy"
            subtitle="Coverage, parties, and the Automotive-specific details for this policy."
            :divider="false"
        />

        <PolicyAutomotiveForm
            :carriers="carriers"
            :agents="agents"
            :subclasses="subclasses"
            :types="types"
            :sources="sources"
            :currencies="currencies"
            :default-currency-id="defaultCurrencyId"
            :entry="entry"
            :route="policiesAutomotiveStore.form()"
            submit-label="Create policy"
        />
    </div>
</template>
