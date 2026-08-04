<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download, Plus } from '@lucide/vue';
import { computed } from 'vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { Spinner } from '@/components/ui/spinner';
import { useFileExport } from '@/composables/useFileExport';
import {
    create as agentsCreate,
    exportMethod as agentsExport,
    index as agentsIndex,
} from '@/routes/agents';
import type { Paginated } from '@/types';
import type { AgentResource } from './partials/agent';
import AgentsTable from './partials/AgentsTable.vue';
import EmptyState from './partials/EmptyState.vue';

const props = defineProps<{
    agents: Paginated<AgentResource>;
    sort: {
        column: string;
        direction: 'asc' | 'desc';
    };
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

const sortLabel = computed(
    () => `Sorted by name · ${props.sort.direction === 'desc' ? 'Z→A' : 'A→Z'}`,
);

const exportUrl = computed(() =>
    agentsExport.url({
        query: {
            sort: props.sort.column,
            direction: props.sort.direction,
        },
    }),
);

const { isExporting, exportFile } = useFileExport();

function exportAgents(): Promise<void> {
    return exportFile(exportUrl.value, 'agents.xlsx', {
        success: 'Agents exported.',
        error: 'Failed to export agents. Please try again.',
    });
}
</script>

<template>
    <Head title="Agents" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="Agents"
            subtitle="Producers and sub-agents in your agency"
            :divider="false"
        >
            <template v-if="hasAgents" #meta>
                <div class="flex flex-wrap items-center gap-2.5">
                    <Badge tone="neutral">{{ agents.meta.total }} agents</Badge>
                    <span class="text-xs text-tertiary">·</span>
                    <span class="text-xs text-tertiary">{{ sortLabel }}</span>
                </div>
            </template>

            <template #actions>
                <template v-if="hasAgents">
                    <Button
                        variant="secondary"
                        size="md"
                        :disabled="isExporting"
                        @click="exportAgents"
                    >
                        <template #leading>
                            <Spinner v-if="isExporting" />
                            <Download v-else />
                        </template>
                        Export
                    </Button>
                </template>
                <Link :href="agentsCreate().url">
                    <Button variant="primary" size="md">
                        <template #leading><Plus /></template>
                        Add Agent
                    </Button>
                </Link>
            </template>
        </PageHeader>

        <AgentsTable
            v-if="hasAgents"
            class="mt-5"
            :agents="agents"
            :sort="sort"
        />
        <EmptyState v-else />
    </div>
</template>
