<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Tab, Tabs } from '@/components/ui/tabs';
import { index as agentsIndex, show as agentsShow } from '@/routes/agents';
import { index as agentsPoliciesIndex } from '@/routes/agents/policies';
import type { AgentResource } from './agent';
import AgentShowHeader from './AgentShowHeader.vue';

const props = defineProps<{
    agent: AgentResource;
    clientsCount: number;
    policiesCount: number;
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
        <AgentShowHeader
            :agent="agent"
            :clients-count="clientsCount"
            :policies-count="policiesCount"
        />

        <div
            class="-mx-4 overflow-x-auto border-b border-border-subtle bg-surface px-4 sm:mx-0 sm:mt-6 sm:overflow-visible sm:border-0 sm:bg-transparent sm:px-0"
        >
            <Tabs class="min-w-max">
                <Tab :href="agentsShow(agent.slug)">Overview</Tab>
                <Tab href="#">Clients</Tab>
                <Tab :href="agentsPoliciesIndex(agent.slug)">Policies</Tab>
            </Tabs>
        </div>

        <slot />
    </div>
</template>
