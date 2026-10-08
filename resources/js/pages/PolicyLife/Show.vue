<script setup lang="ts">
import PolicyDetailShell from '../Policies/partials/PolicyDetailShell.vue';
import PolicyFinancialsCard from '../Policies/partials/PolicyFinancialsCard.vue';
import PolicyPartiesCard from '../Policies/partials/PolicyPartiesCard.vue';
import PolicyTermCard from '../Policies/partials/PolicyTermCard.vue';
import LifeDetailCard from './partials/LifeDetailCard.vue';
import type { PolicyLifeResource } from './partials/policy';

defineProps<{
    policy: PolicyLifeResource;
}>();
</script>

<template>
    <PolicyDetailShell :policy="policy">
        <div
            class="grid grid-cols-1 items-start gap-5 pt-6 lg:grid-cols-[1fr_360px]"
        >
            <!-- Main column -->
            <div class="flex min-w-0 flex-col gap-4">
                <PolicyFinancialsCard :policy="policy" />
                <LifeDetailCard :policy="policy" />
            </div>

            <!-- Sidebar -->
            <div class="flex flex-col gap-4">
                <PolicyPartiesCard :policy="policy" />
                <PolicyTermCard :policy="policy" />
            </div>
        </div>
    </PolicyDetailShell>
</template>
