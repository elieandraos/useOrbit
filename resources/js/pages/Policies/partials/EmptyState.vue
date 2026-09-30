<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Plus, SearchX, Shield } from '@lucide/vue';
import Button from '@/components/ui/button/Button.vue';
import {
    create as policiesCreate,
    index as policiesIndex,
} from '@/routes/policies';

defineProps<{
    filtered?: boolean;
}>();

function clearFilters() {
    router.get(policiesIndex.url());
}
</script>

<template>
    <div class="flex flex-1 items-center justify-center py-16">
        <div
            class="w-full max-w-[480px] rounded-lg border border-border bg-surface px-9 py-11 text-center shadow-card"
        >
            <div
                class="mx-auto mb-4 flex size-14 items-center justify-center rounded-lg bg-accent-bg text-accent"
            >
                <SearchX v-if="filtered" class="size-6" />
                <Shield v-else class="size-6" />
            </div>
            <h2 class="text-lg font-semibold text-primary">
                {{
                    filtered
                        ? 'No policies match your filters'
                        : 'No policies yet'
                }}
            </h2>
            <p
                class="mx-auto mt-2 max-w-[360px] text-sm leading-relaxed text-secondary"
            >
                <template v-if="filtered"
                    >Try adjusting or clearing your filters to see more
                    results.</template
                >
                <template v-else
                    >Issue your first policy to start tracking effective and
                    expiry dates, premiums, discounts, and settlement payments
                    across Medical, Automotive, Fire, Life, Expat, and Travel
                    lines.</template
                >
            </p>
            <div class="mt-6 flex justify-center">
                <Button
                    v-if="filtered"
                    variant="secondary"
                    size="md"
                    @click="clearFilters"
                >
                    Clear filters
                </Button>
                <Link v-else :href="policiesCreate().url">
                    <Button variant="primary" size="md">
                        <template #leading><Plus /></template>
                        New Policy
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>
