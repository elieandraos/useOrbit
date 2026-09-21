<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Paginated } from '@/types';
import type { PolicyResource } from '@/types/policy';
import type { CarrierResource } from '../Carriers/partials/carrier';
import CarrierDetailShell from '../Carriers/partials/CarrierDetailShell.vue';
import PoliciesTable from '../Policies/partials/PoliciesTable.vue';

const props = defineProps<{
    carrier: CarrierResource;
    policies: Paginated<PolicyResource>;
    policiesCount: number;
}>();

const hasPolicies = computed(() => props.policies.data.length > 0);
</script>

<template>
    <Head :title="`${carrier.name} · Policies`" />

    <CarrierDetailShell :carrier="carrier" :policies-count="policiesCount">
        <div class="mt-6">
            <PoliciesTable v-if="hasPolicies" :policies="policies" />
            <div
                v-else
                class="rounded-lg border border-border bg-surface py-16 text-center text-secondary shadow-card"
            >
                No policies yet for this carrier.
            </div>
        </div>
    </CarrierDetailShell>
</template>
