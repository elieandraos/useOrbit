<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import { store as policiesFireStore } from '@/routes/policies/fire';
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
    types: Option[];
    statuses: Option[];
    sources: Option[];
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
            :types="types"
            :statuses="statuses"
            :sources="sources"
            :countries="countries"
            :defaults="defaults"
            :route="policiesFireStore.form()"
            submit-label="Create policy"
        />
    </div>
</template>
