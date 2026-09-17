<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import type { Paginated } from '@/types';
import type { PolicyResource } from '@/types/policy';
import EmptyState from './partials/EmptyState.vue';

const props = defineProps<{
    policies: Paginated<PolicyResource>;
}>();

const hasPolicies = computed(() => props.policies.data.length > 0);
</script>

<template>
    <Head title="Policies" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="Policies"
            subtitle="All policies across Medical, Automotive, Fire, Life, Expat, and Travel"
        />

        <div v-if="hasPolicies" class="mt-5 flex-1" />
        <EmptyState v-else />
    </div>
</template>
