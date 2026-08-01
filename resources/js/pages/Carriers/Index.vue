<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download, Filter, Plus } from '@lucide/vue';
import { computed } from 'vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { create as carriersCreate, index as carriersIndex } from '@/routes/carriers';
import type { Paginated } from '@/types';
import type { CarrierResource } from './partials/carrier';
import CarriersTable from './partials/CarriersTable.vue';
import EmptyState from './partials/EmptyState.vue';

const props = defineProps<{
    carriers: Paginated<CarrierResource>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Carriers',
                href: carriersIndex(),
            },
        ],
    },
});

const hasCarriers = computed(() => props.carriers.data.length > 0);
</script>

<template>
    <Head title="Carriers" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="Carriers"
            subtitle="Insurance carriers — branches, contacts, and policies in force"
            :divider="false"
        >
            <template v-if="hasCarriers" #meta>
                <div class="flex flex-wrap items-center gap-2.5">
                    <Badge tone="neutral">{{ carriers.meta.total }} carriers</Badge>
                    <span class="text-xs text-tertiary">·</span>
                    <span class="text-xs text-tertiary">Sorted by name · A→Z</span>
                </div>
            </template>

            <template #actions>
                <template v-if="hasCarriers">
                    <Button variant="secondary" size="md">
                        <template #leading><Filter /></template>
                        Filters
                    </Button>
                    <Button variant="secondary" size="md">
                        <template #leading><Download /></template>
                        Export
                    </Button>
                </template>
                <Link :href="carriersCreate().url">
                    <Button variant="primary" size="md">
                        <template #leading><Plus /></template>
                        Add new carrier
                    </Button>
                </Link>
            </template>
        </PageHeader>

        <CarriersTable v-if="hasCarriers" class="mt-5" :carriers="carriers" />
        <EmptyState v-else />
    </div>
</template>
