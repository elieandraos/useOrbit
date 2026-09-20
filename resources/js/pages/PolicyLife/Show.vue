<script setup lang="ts">
import CoverageCard from './partials/CoverageCard.vue';
import CoveragePeriodCard from './partials/CoveragePeriodCard.vue';
import FinancialsCard from './partials/FinancialsCard.vue';
import LifeDetailCard from './partials/LifeDetailCard.vue';
import PartiesCard from './partials/PartiesCard.vue';
import type { PolicyLifeResource } from './partials/policy';
import PolicyLifeDetailShell from './partials/PolicyLifeDetailShell.vue';
import StatusOriginCard from './partials/StatusOriginCard.vue';

defineProps<{
    policy: PolicyLifeResource;
}>();
</script>

<template>
    <PolicyLifeDetailShell :policy="policy">
        <div class="grid grid-cols-1 items-start gap-5 pt-6 lg:grid-cols-2">
            <CoverageCard :policy="policy" />
            <PartiesCard :policy="policy" />
            <CoveragePeriodCard :policy="policy" />
            <FinancialsCard :policy="policy" />
            <StatusOriginCard :policy="policy" />
            <LifeDetailCard :policy="policy" />
        </div>
    </PolicyLifeDetailShell>
</template>
