<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useFileExport } from '@/composables/useFileExport';
import { exportMethod as policiesExport } from '@/routes/policies';
import type { Paginated } from '@/types';
import type { PolicyResource } from '@/types/policy';
import EmptyState from './partials/EmptyState.vue';
import FiltersDrawer from './partials/FiltersDrawer.vue';
import PoliciesIndexHeader from './partials/PoliciesIndexHeader.vue';
import PoliciesTable from './partials/PoliciesTable.vue';

interface Option {
    label: string;
    value: string;
}

interface CarrierOption {
    id: number;
    name: string;
}

const props = defineProps<{
    policies: Paginated<PolicyResource>;
    statuses: Option[];
    types: Option[];
    classes: Option[];
    sources: Option[];
    carriers: CarrierOption[];
    filters: {
        search: string | null;
        status: string | null;
        type: string | null;
        class: string[] | null;
        carrier_id: string | number | null;
        source: string | null;
        effective_from: string | null;
        effective_to: string | null;
        amount_min: string | number | null;
        amount_max: string | number | null;
    };
}>();

const hasPolicies = computed(() => props.policies.data.length > 0);

const filtersOpen = ref(false);

function isActiveFilter(value: unknown): boolean {
    return (
        value !== null &&
        value !== '' &&
        !(Array.isArray(value) && value.length === 0)
    );
}

const activeFilterCount = computed(
    () => Object.values(props.filters).filter(isActiveFilter).length,
);

// Mirrors the currently applied filters so the download matches what's on screen.
// The index has no sort param, so the export falls back to the same default order.
const exportUrl = computed(() =>
    policiesExport.url({
        query: Object.fromEntries(
            Object.entries(props.filters).filter(([, value]) =>
                isActiveFilter(value),
            ),
        ),
    }),
);

const { isExporting, exportFile } = useFileExport();

function exportPolicies(): Promise<void> {
    return exportFile(exportUrl.value, 'policies.xlsx', {
        success: 'Policies exported.',
        error: 'Failed to export policies. Please try again.',
    });
}
</script>

<template>
    <Head title="Policies" />

    <div class="flex flex-1 flex-col">
        <PoliciesIndexHeader
            v-model:open="filtersOpen"
            :has-policies="hasPolicies"
            :active-filter-count="activeFilterCount"
            :is-exporting="isExporting"
            @export="exportPolicies"
        />

        <div v-if="hasPolicies" class="mt-5 flex-1">
            <PoliciesTable :policies="policies" />
        </div>
        <EmptyState v-else :filtered="activeFilterCount > 0" />

        <FiltersDrawer
            v-model:open="filtersOpen"
            :statuses="statuses"
            :types="types"
            :classes="classes"
            :sources="sources"
            :carriers="carriers"
            :filters="filters"
        />
    </div>
</template>
