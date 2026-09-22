<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Filter } from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import type { Paginated } from '@/types';
import type { PolicyResource } from '@/types/policy';
import EmptyState from './partials/EmptyState.vue';
import FiltersDrawer from './partials/FiltersDrawer.vue';
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

const activeFilterCount = computed(
    () =>
        Object.values(props.filters).filter(
            (value) =>
                value !== null &&
                value !== '' &&
                !(Array.isArray(value) && value.length === 0),
        ).length,
);
</script>

<template>
    <Head title="Policies" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="Policies"
            subtitle="All policies across Medical, Automotive, Fire, Life, Expat, and Travel"
        >
            <template #actions>
                <Button
                    v-if="hasPolicies || activeFilterCount > 0"
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
            </template>
        </PageHeader>

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
