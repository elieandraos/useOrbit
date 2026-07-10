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
    create as clientsCreate,
    exportMethod as clientsExport,
} from '@/routes/clients';
import type { Paginated } from '@/types';
import type { ClientResource } from './partials/client';
import ClientsTable from './partials/ClientsTable.vue';
import EmptyState from './partials/EmptyState.vue';
import FiltersDrawer from './partials/FiltersDrawer.vue';

const props = defineProps<{
    clients: Paginated<ClientResource>;
    genders: { label: string; value: string }[];
    sort: {
        column: string;
        direction: 'asc' | 'desc';
    };
    filters: {
        search: string | null;
        gender: string | null;
        enrolled_from: string | null;
        enrolled_to: string | null;
        age_min: string | number | null;
        age_max: string | number | null;
        archived: string | number | boolean | null;
    };
}>();

const hasClients = computed(() => props.clients.data.length > 0);

const filtersOpen = ref(false);

// Mirrors the currently applied filters/sort so the download matches what's on screen.
const exportUrl = computed(() =>
    clientsExport.url({
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

function exportClients(): Promise<void> {
    return exportFile(exportUrl.value, 'clients.xlsx', {
        success: 'Clients exported.',
        error: 'Failed to export clients. Please try again.',
    });
}

const activeFilterCount = computed(
    () =>
        Object.values(props.filters).filter(
            (value) => value !== null && value !== '',
        ).length,
);

const otherFilterCount = computed(
    () =>
        Object.entries(props.filters).filter(
            ([key, value]) =>
                key !== 'archived' && value !== null && value !== '',
        ).length,
);

const isArchivedView = computed(
    () =>
        props.filters.archived === true ||
        props.filters.archived === 1 ||
        props.filters.archived === '1',
);

const sortColumnLabels: Record<string, string> = {
    name: 'name',
    email: 'email',
    enrollment_date: 'enrollment date',
};

const sortLabel = computed(() => {
    const column = sortColumnLabels[props.sort.column] ?? props.sort.column;

    if (props.sort.column === 'enrollment_date') {
        return `Sorted by ${column} · ${props.sort.direction === 'desc' ? 'newest first' : 'oldest first'}`;
    }

    return `Sorted by ${column} · ${props.sort.direction === 'desc' ? 'Z–A' : 'A–Z'}`;
});
</script>

<template>
    <Head title="Clients" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="Clients"
            subtitle="Manage individual and corporate insurance clients"
        >
            <template v-if="hasClients" #meta>
                <div class="flex flex-wrap items-center gap-2.5">
                    <Badge :tone="isArchivedView ? 'warning' : 'neutral'">
                        {{ clients.meta.total }}
                        {{ isArchivedView ? 'archived clients' : 'clients' }}
                    </Badge>
                    <span class="text-xs text-tertiary">·</span>
                    <span class="text-xs text-tertiary">{{ sortLabel }}</span>
                </div>
            </template>

            <template #actions>
                <Button
                    v-if="hasClients || activeFilterCount > 0"
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
                <template v-if="hasClients">
                    <Button
                        variant="secondary"
                        size="md"
                        :disabled="isExporting"
                        @click="exportClients"
                    >
                        <template #leading>
                            <Spinner v-if="isExporting" />
                            <Download v-else />
                        </template>
                        Export
                    </Button>
                    <Link :href="clientsCreate().url">
                        <Button variant="primary" size="md">
                            <template #leading><Plus /></template>
                            Add New Client
                        </Button>
                    </Link>
                </template>
            </template>
        </PageHeader>

        <ClientsTable
            v-if="hasClients"
            class="mt-5"
            :clients="clients"
            :sort="sort"
        />
        <EmptyState
            v-else
            :filtered="otherFilterCount > 0"
            :archived="isArchivedView"
        />

        <FiltersDrawer
            v-model:open="filtersOpen"
            :genders="genders"
            :filters="filters"
        />
    </div>
</template>
