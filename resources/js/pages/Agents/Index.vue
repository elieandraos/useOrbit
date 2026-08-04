<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download, Filter, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { Spinner } from '@/components/ui/spinner';
import { useFileExport } from '@/composables/useFileExport';
import {
    create as agentsCreate,
    exportMethod as agentsExport,
} from '@/routes/agents';
import type { Paginated } from '@/types';
import type { AgentResource } from './partials/agent';
import AgentsTable from './partials/AgentsTable.vue';
import EmptyState from './partials/EmptyState.vue';
import FiltersDrawer from './partials/FiltersDrawer.vue';

const props = defineProps<{
    agents: Paginated<AgentResource>;
    sort: {
        column: string;
        direction: 'asc' | 'desc';
    };
    filters: {
        search: string | null;
        archived: string | number | boolean | null;
    };
}>();

const hasAgents = computed(() => props.agents.data.length > 0);

const sortLabel = computed(
    () => `Sorted by name · ${props.sort.direction === 'desc' ? 'Z→A' : 'A→Z'}`,
);

const filtersOpen = ref(false);

const isArchivedView = computed(
    () =>
        props.filters.archived === true ||
        props.filters.archived === 1 ||
        props.filters.archived === '1',
);

const otherFilterCount = computed(
    () =>
        Object.entries(props.filters).filter(
            ([key, value]) =>
                key !== 'archived' && value !== null && value !== '',
        ).length,
);

const activeFilterCount = computed(
    () => otherFilterCount.value + (isArchivedView.value ? 1 : 0),
);

// Mirrors the currently applied filters/sort so the download matches what's on screen.
const exportUrl = computed(() =>
    agentsExport.url({
        query: {
            ...Object.fromEntries(
                Object.entries(props.filters).filter(
                    ([, value]) => value !== null && value !== '',
                ),
            ),
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
                <div class="hidden flex-wrap items-center gap-2.5 sm:flex">
                    <Badge :tone="isArchivedView ? 'warning' : 'neutral'">
                        {{ agents.meta.total }}
                        {{ isArchivedView ? 'archived agents' : 'agents' }}
                    </Badge>
                    <span class="text-xs text-tertiary">·</span>
                    <span class="text-xs text-tertiary">{{ sortLabel }}</span>
                </div>
            </template>

            <template #actions>
                <!-- Mobile: Add Agent leads, Filters + icon-only Export trail on the right -->
                <div
                    class="flex w-full items-center justify-between gap-2 sm:hidden"
                >
                    <Link v-if="hasAgents" :href="agentsCreate().url">
                        <Button variant="primary" size="md">
                            <template #leading><Plus /></template>
                            Add Agent
                        </Button>
                    </Link>
                    <div v-else />

                    <div class="flex items-center gap-1.5">
                        <Button
                            v-if="hasAgents || activeFilterCount > 0"
                            variant="secondary"
                            size="md"
                            @click="filtersOpen = true"
                        >
                            <template #leading><Filter /></template>
                            Filters
                            <Badge v-if="activeFilterCount > 0" tone="accent">{{
                                activeFilterCount
                            }}</Badge>
                        </Button>
                        <button
                            v-if="hasAgents"
                            type="button"
                            title="Export"
                            :disabled="isExporting"
                            class="inline-flex size-[34px] shrink-0 items-center justify-center rounded-md border border-border bg-surface text-secondary shadow-card transition-colors hover:bg-sunken disabled:pointer-events-none disabled:opacity-50"
                            @click="exportAgents"
                        >
                            <Spinner v-if="isExporting" />
                            <Download v-else class="size-4" />
                        </button>
                    </div>
                </div>

                <!-- Desktop (`sm` and above): Filters, Export, Add Agent -->
                <Button
                    v-if="hasAgents || activeFilterCount > 0"
                    variant="secondary"
                    size="md"
                    class="hidden sm:inline-flex"
                    @click="filtersOpen = true"
                >
                    <template #leading><Filter /></template>
                    Filters
                    <Badge v-if="activeFilterCount > 0" tone="accent">{{
                        activeFilterCount
                    }}</Badge>
                </Button>
                <template v-if="hasAgents">
                    <Button
                        variant="secondary"
                        size="md"
                        class="hidden sm:inline-flex"
                        :disabled="isExporting"
                        @click="exportAgents"
                    >
                        <template #leading>
                            <Spinner v-if="isExporting" />
                            <Download v-else />
                        </template>
                        Export
                    </Button>
                    <Link
                        :href="agentsCreate().url"
                        class="hidden sm:inline-flex"
                    >
                        <Button variant="primary" size="md">
                            <template #leading><Plus /></template>
                            Add Agent
                        </Button>
                    </Link>
                </template>
            </template>
        </PageHeader>

        <AgentsTable
            v-if="hasAgents"
            class="mt-5"
            :agents="agents"
            :sort="sort"
        />
        <EmptyState
            v-else
            :filtered="otherFilterCount > 0"
            :archived="isArchivedView"
        />

        <FiltersDrawer v-model:open="filtersOpen" :filters="filters" />
    </div>
</template>
