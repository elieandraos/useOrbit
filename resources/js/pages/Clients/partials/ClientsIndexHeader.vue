<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Download, Filter, Plus } from '@lucide/vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { Spinner } from '@/components/ui/spinner';
import { create as clientsCreate } from '@/routes/clients';

defineProps<{
    hasClients: boolean;
    total: number;
    isArchivedView: boolean;
    sortLabel: string;
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
        title="Clients"
        subtitle="Manage individual and corporate insurance clients"
    >
        <template v-if="hasClients" #meta>
            <div class="flex flex-wrap items-center gap-2.5">
                <Badge :tone="isArchivedView ? 'warning' : 'neutral'">
                    {{ total }}
                    {{ isArchivedView ? 'archived clients' : 'clients' }}
                </Badge>
                <span class="text-xs text-tertiary">·</span>
                <span class="text-xs text-tertiary">{{ sortLabel }}</span>
            </div>
        </template>

        <template #actions>
            <!-- Mobile: Add New Client leads, Filters + icon-only Export trail on the right -->
            <div
                class="flex w-full items-center justify-between gap-2 sm:hidden"
            >
                <Link v-if="hasClients" :href="clientsCreate().url">
                    <Button variant="primary" size="md">
                        <template #leading><Plus /></template>
                        Add New Client
                    </Button>
                </Link>
                <div v-else />

                <div class="flex items-center gap-1.5">
                    <Button
                        v-if="hasClients || activeFilterCount > 0"
                        variant="secondary"
                        size="md"
                        @click="open = true"
                    >
                        <template #leading><Filter /></template>
                        Filters
                        <Badge v-if="activeFilterCount > 0" tone="accent">{{
                            activeFilterCount
                        }}</Badge>
                    </Button>
                    <button
                        v-if="hasClients"
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

            <!-- Desktop (`sm` and above): Filters, Export, Add New Client -->
            <Button
                v-if="hasClients || activeFilterCount > 0"
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
            <template v-if="hasClients">
                <Button
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
                <Link :href="clientsCreate().url" class="hidden sm:inline-flex">
                    <Button variant="primary" size="md">
                        <template #leading><Plus /></template>
                        Add New Client
                    </Button>
                </Link>
            </template>
        </template>
    </PageHeader>
</template>
