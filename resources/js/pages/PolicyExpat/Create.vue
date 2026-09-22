<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import { store as policiesExpatStore } from '@/routes/policies/expat';
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
    clients: EntityOption[];
    carriers: EntityOption[];
    agents: EntityOption[];
    types: Option[];
    statuses: Option[];
    sources: Option[];
    coverageZones: Option[];
    genders: Option[];
    countries: CountryOption[];
}>();

const query = new URLSearchParams(window.location.search);
const defaults: Record<string, string> = {};

for (const [key, value] of query.entries()) {
    if (value) {
        defaults[key] = value;
    }
}

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
            :clients="clients"
            :carriers="carriers"
            :agents="agents"
            :types="types"
            :statuses="statuses"
            :sources="sources"
            :coverage-zones="coverageZones"
            :genders="genders"
            :countries="countries"
            :defaults="defaults"
            :route="policiesExpatStore.form()"
            submit-label="Create policy"
        />
    </div>
</template>
