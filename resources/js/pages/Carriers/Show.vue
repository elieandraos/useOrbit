<script setup lang="ts">
import BranchesCard from './partials/BranchesCard.vue';
import type { CarrierResource } from './partials/carrier';
import CarrierDetailShell from './partials/CarrierDetailShell.vue';
import CarrierInformationCard from './partials/CarrierInformationCard.vue';
import ContactCard from './partials/ContactCard.vue';
import QuickStatsCard from './partials/QuickStatsCard.vue';

defineProps<{
    carrier: CarrierResource;
}>();

const policiesCount = 0;
</script>

<template>
    <CarrierDetailShell :carrier="carrier" :policies-count="policiesCount">
        <div
            class="grid grid-cols-1 items-start gap-5 pt-6 lg:grid-cols-[380px_1fr]"
        >
            <!-- Left column -->
            <div class="flex flex-col gap-4">
                <CarrierInformationCard :carrier="carrier" />
                <ContactCard :carrier="carrier" />
                <QuickStatsCard />
            </div>

            <!-- Right column -->
            <div class="flex flex-col gap-4">
                <BranchesCard :carrier="carrier" />
            </div>
        </div>
    </CarrierDetailShell>
</template>
