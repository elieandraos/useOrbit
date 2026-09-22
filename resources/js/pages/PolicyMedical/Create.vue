<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as policiesIndex } from '@/routes/policies';
import { store as policiesMedicalStore } from '@/routes/policies/medical';
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
    types: Option[];
    statuses: Option[];
    sources: Option[];
    coverageScopes: Option[];
    classTiers: Option[];
    genders: Option[];
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
            :types="types"
            :statuses="statuses"
            :sources="sources"
            :coverage-scopes="coverageScopes"
            :class-tiers="classTiers"
            :genders="genders"
            :defaults="defaults"
            :route="policiesMedicalStore.form()"
            submit-label="Create policy"
        />
    </div>
</template>
