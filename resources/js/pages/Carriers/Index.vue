<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed } from 'vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import Button from '@/components/ui/button/Button.vue';
import { create as carriersCreate, index as carriersIndex } from '@/routes/carriers';
import type { Paginated } from '@/types';
import type { CarrierResource } from './partials/carrier';
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
            <template #actions>
                <Link :href="carriersCreate().url">
                    <Button variant="primary" size="md">
                        <template #leading><Plus /></template>
                        Add new carrier
                    </Button>
                </Link>
            </template>
        </PageHeader>

        <EmptyState v-if="!hasCarriers" />
    </div>
</template>
