<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import { index as agentsIndex, store as agentsStore } from '@/routes/agents';
import AgentForm from './partials/AgentForm.vue';

defineProps<{
    countries: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Agents',
                href: agentsIndex(),
            },
            {
                title: 'Create',
            },
        ],
    },
});
</script>

<template>
    <Head title="Add Agent" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="Add Agent"
            subtitle="Capture the agent's personal information, contact details, and address."
            :divider="false"
        />

        <AgentForm
            :countries="countries"
            :route="agentsStore.form()"
            submit-label="Create agent"
        />
    </div>
</template>
