<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Download, Filter, Plus } from '@lucide/vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { Spinner } from '@/components/ui/spinner';
import { create as policiesCreate } from '@/routes/policies';

defineProps<{
    hasPolicies: boolean;
    activeFilterCount: number;
    isExporting: boolean;
}>();

defineEmits<{
    export: [];
}>();

const open = defineModel<boolean>('open', { default: false });
</script>

<template>
    <PageHeader
        title="Policies"
        subtitle="All policies across Medical, Automotive, Fire, Life, Expat, and Travel"
    >
        <template #actions>
            <!-- Mobile: New Policy leads, Filters + icon-only Export trail on the right -->
            <div
                v-if="hasPolicies || activeFilterCount > 0"
                class="flex w-full items-center justify-between gap-2 sm:hidden"
            >
                <Link :href="policiesCreate().url">
                    <Button variant="primary" size="md">
                        <template #leading><Plus /></template>
                        New Policy
                    </Button>
                </Link>

                <div class="flex items-center gap-1.5">
                    <Button variant="secondary" size="md" @click="open = true">
                        <template #leading><Filter /></template>
                        Filters
                        <Badge v-if="activeFilterCount > 0" tone="accent">{{
                            activeFilterCount
                        }}</Badge>
                    </Button>
                    <button
                        v-if="hasPolicies"
                        type="button"
                        title="Export"
                        :disabled="isExporting"
                        class="inline-flex size-[34px] shrink-0 items-center justify-center rounded-md border border-border bg-surface text-secondary shadow-card transition-colors hover:bg-sunken disabled:pointer-events-none disabled:opacity-50"
                        @click="$emit('export')"
                    >
                        <Spinner v-if="isExporting" />
                        <Download v-else class="size-4" />
                    </button>
                </div>
            </div>

            <!-- Desktop (`sm` and above): Filters, Export, New Policy -->
            <template v-if="hasPolicies || activeFilterCount > 0">
                <Button
                    variant="secondary"
                    size="md"
                    class="hidden sm:inline-flex"
                    @click="open = true"
                >
                    <template #leading><Filter /></template>
                    Filters
                    <Badge v-if="activeFilterCount > 0" tone="accent">{{
                        activeFilterCount
                    }}</Badge>
                </Button>
                <Button
                    v-if="hasPolicies"
                    variant="secondary"
                    size="md"
                    class="hidden sm:inline-flex"
                    :disabled="isExporting"
                    @click="$emit('export')"
                >
                    <template #leading>
                        <Spinner v-if="isExporting" />
                        <Download v-else />
                    </template>
                    Export
                </Button>
                <Link
                    :href="policiesCreate().url"
                    class="hidden sm:inline-flex"
                >
                    <Button variant="primary" size="md">
                        <template #leading><Plus /></template>
                        New Policy
                    </Button>
                </Link>
            </template>
        </template>
    </PageHeader>
</template>
