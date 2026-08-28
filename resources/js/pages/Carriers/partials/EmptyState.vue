<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Archive, Building2, Plus, SearchX } from '@lucide/vue';
import Button from '@/components/ui/button/Button.vue';
import {
    create as carriersCreate,
    index as carriersIndex,
} from '@/routes/carriers';

defineProps<{
    filtered?: boolean;
    archived?: boolean;
}>();

function clearFilters() {
    router.get(carriersIndex.url());
}
</script>

<template>
    <div class="flex flex-1 items-center justify-center py-16">
        <div
            class="w-full max-w-[540px] rounded-lg border border-border bg-surface px-9 py-11 text-center shadow-card"
        >
            <div
                class="mx-auto mb-4 flex size-14 items-center justify-center rounded-lg bg-accent-bg text-accent"
            >
                <Archive v-if="archived" class="size-6" />
                <SearchX v-else-if="filtered" class="size-6" />
                <Building2 v-else class="size-6" />
            </div>
            <h2 class="text-lg font-semibold text-primary">
                {{
                    archived && filtered
                        ? 'No archived carriers match your filters'
                        : archived
                          ? 'No archived carriers'
                          : filtered
                            ? 'No carriers match your filters'
                            : 'No carriers yet'
                }}
            </h2>
            <p
                class="mx-auto mt-2 max-w-[380px] text-sm leading-relaxed text-secondary"
            >
                <template v-if="archived && !filtered"
                    >Carriers that have been archived will appear
                    here.</template
                >
                <template v-else-if="filtered"
                    >Try adjusting or clearing your filters to see more
                    results.</template
                >
                <template v-else
                    >Add your first insurance carrier. Capture branches, contact
                    people, and the policies you'll renew with them.</template
                >
            </p>
            <div class="mt-6 flex justify-center">
                <Button
                    v-if="archived || filtered"
                    variant="secondary"
                    size="md"
                    @click="clearFilters"
                >
                    Clear filters
                </Button>
                <Link v-else :href="carriersCreate().url">
                    <Button variant="primary" size="md">
                        <template #leading><Plus /></template>
                        Add new carrier
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>
