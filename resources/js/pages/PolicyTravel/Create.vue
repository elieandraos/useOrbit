<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import { store as policiesTravelStore } from '@/routes/policies/travel';
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
    clients: EntityOption[];
    carriers: EntityOption[];
    agents: EntityOption[];
    types: Option[];
    statuses: Option[];
    sources: Option[];
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
            :clients="clients"
            :carriers="carriers"
            :agents="agents"
            :types="types"
            :statuses="statuses"
            :sources="sources"
            :defaults="defaults"
            :route="policiesTravelStore.form()"
            submit-label="Create policy"
        />
    </div>
</template>
