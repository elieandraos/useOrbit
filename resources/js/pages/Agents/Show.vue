<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { index as agentsIndex } from '@/routes/agents';
import type { AgentResource } from './partials/agent';
import AgentShowHeader from './partials/AgentShowHeader.vue';
import ContactCard from './partials/ContactCard.vue';
import PersonalInformationCard from './partials/PersonalInformationCard.vue';

const props = defineProps<{
    agent: AgentResource;
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Agents',
            href: agentsIndex(),
        },
        {
            title: props.agent.full_name,
        },
    ],
    breadcrumbsSurface: true,
});
</script>

<template>
    <Head :title="agent.full_name" />

    <div class="flex flex-1 flex-col">
        <AgentShowHeader :agent="agent" />

        <div class="grid grid-cols-1 items-start gap-5 pt-6 lg:grid-cols-2">
            <PersonalInformationCard :agent="agent" />
            <ContactCard :agent="agent" />
        </div>
    </div>
</template>
