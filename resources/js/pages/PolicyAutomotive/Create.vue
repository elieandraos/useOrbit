<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import { store as policiesAutomotiveStore } from '@/routes/policies/automotive';
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
            :clients="clients"
            :carriers="carriers"
            :agents="agents"
            :types="types"
            :statuses="statuses"
            :sources="sources"
            :defaults="defaults"
            :route="policiesAutomotiveStore.form()"
            submit-label="Create policy"
        />
    </div>
</template>
