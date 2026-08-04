<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed } from 'vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import Button from '@/components/ui/button/Button.vue';
import { create as agentsCreate, index as agentsIndex } from '@/routes/agents';
import type { Paginated } from '@/types';
import type { AgentResource } from './partials/agent';
import EmptyState from './partials/EmptyState.vue';

const props = defineProps<{
    agents: Paginated<AgentResource>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Agents',
                href: agentsIndex(),
            },
        ],
    },
});

const hasAgents = computed(() => props.agents.data.length > 0);
</script>

<template>
    <Head title="Agents" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="Agents"
            subtitle="Producers and sub-agents in your agency"
            :divider="false"
        >
            <template #actions>
                <Link :href="agentsCreate().url">
                    <Button variant="primary" size="md">
                        <template #leading><Plus /></template>
                        Add Agent
                    </Button>
                </Link>
            </template>
        </PageHeader>

        <EmptyState v-if="!hasAgents" />
    </div>
</template>
