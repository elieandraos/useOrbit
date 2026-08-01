<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useFileExport } from '@/composables/useFileExport';
import { exportMethod as clientsExport } from '@/routes/clients';
import type { Paginated } from '@/types';
import type { ClientResource } from './partials/client';
import ClientsIndexHeader from './partials/ClientsIndexHeader.vue';
import ClientsTable from './partials/ClientsTable.vue';
import EmptyState from './partials/EmptyState.vue';
import FiltersDrawer from './partials/FiltersDrawer.vue';

const props = defineProps<{
    clients: Paginated<ClientResource>;
    genders: { label: string; value: string }[];
    clientTypes: { label: string; value: string }[];
    sort: {
        column: string;
        direction: 'asc' | 'desc';
    };
    filters: {
        search: string | null;
        client_type: string | null;
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

const activeFilterCount = computed(
    () => otherFilterCount.value + (isArchivedView.value ? 1 : 0),
);

const sortColumnLabels: Record<string, string> = {
    name: 'name',
    type: 'type',
    enrollment_date: 'enrollment date',
};

const sortLabel = computed(() => {
    const column = sortColumnLabels[props.sort.column] ?? props.sort.column;

    if (props.sort.column === 'enrollment_date') {
        return `Sorted by ${column} · ${props.sort.direction === 'desc' ? 'newest first' : 'oldest first'}`;
    }

    return `Sorted by ${column} · ${props.sort.direction === 'desc' ? 'Z–A' : 'A–Z'}`;
});

// Compact form of sortLabel for the mobile count/sort caption above the card list.
const sortLabelShort = computed(() => {
    const column = sortColumnLabels[props.sort.column] ?? props.sort.column;

    if (props.sort.column === 'enrollment_date') {
        return props.sort.direction === 'desc'
            ? 'Newest first'
            : 'Oldest first';
    }

    return `${column} ${props.sort.direction === 'desc' ? 'Z–A' : 'A–Z'}`;
});
</script>

<template>
    <Head title="Clients" />

    <div class="flex flex-1 flex-col">
        <ClientsIndexHeader
            v-model:open="filtersOpen"
            :has-clients="hasClients"
            :total="clients.meta.total"
            :is-archived-view="isArchivedView"
            :sort-label="sortLabel"
            :active-filter-count="activeFilterCount"
            :is-exporting="isExporting"
            @export="exportClients"
        />

        <ClientsTable
            v-if="hasClients"
            class="mt-5"
            :clients="clients"
            :sort="sort"
            :sort-label="sortLabelShort"
        />
        <EmptyState
            v-else
            :filtered="otherFilterCount > 0"
            :archived="isArchivedView"
        />

        <FiltersDrawer
            v-model:open="filtersOpen"
            :genders="genders"
            :client-types="clientTypes"
            :filters="filters"
        />
    </div>
</template>
