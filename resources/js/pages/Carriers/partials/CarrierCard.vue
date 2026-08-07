<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Archive,
    ArchiveRestore,
    Building2,
    Eye,
    MoreHorizontal,
    Pencil,
} from '@lucide/vue';
import { computed } from 'vue';
import { Avatar } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge/Badge.vue';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { Separator } from '@/components/ui/separator';
import { useAuth } from '@/composables/useAuth';
import { edit as carriersEdit, show as carriersShow } from '@/routes/carriers';
import type { CarrierResource } from './carrier';

const props = defineProps<{
    carrier: CarrierResource;
}>();

const emit = defineEmits<{
    archive: [carrier: CarrierResource];
    unarchive: [carrier: CarrierResource];
}>();

const { isPrivileged } = useAuth();

function goToCarrier(carrier: CarrierResource) {
    router.visit(carriersShow(carrier.slug).url);
}

const branchCities = computed(() =>
    (props.carrier.branches ?? [])
        .map((branch) => branch.city)
        .filter((city): city is string => !!city)
        .join(', '),
);
</script>

<template>
    <!-- Rendered only below `md` by CarriersTable.vue, in place of a table row -->
    <div
        class="cursor-pointer rounded-lg border border-border bg-surface p-3.5 shadow-card transition-colors active:bg-sunken"
        @click="goToCarrier(carrier)"
    >
        <div class="flex items-start gap-3">
            <Avatar :name="carrier.name" :size="36" />
            <div class="min-w-0 flex-1">
                <div class="truncate text-[14px] font-medium text-primary">
                    {{ carrier.name }}
                </div>
                <div
                    v-if="carrier.website"
                    class="mt-0.5 truncate font-mono text-[11.5px] text-tertiary"
                >
                    {{ carrier.website }}
                </div>
            </div>

            <div @click.stop>
                <DropMenu>
                    <template #trigger>
                        <button
                            class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-secondary transition-colors hover:bg-sunken"
                        >
                            <MoreHorizontal class="size-4" />
                        </button>
                    </template>

                    <DropMenuItem :href="carriersShow(carrier.slug).url">
                        <template #leading><Eye class="size-4" /></template>
                        View
                    </DropMenuItem>
                    <DropMenuItem :href="carriersEdit(carrier.slug).url">
                        <template #leading><Pencil class="size-4" /></template>
                        Edit
                    </DropMenuItem>
                    <Separator v-if="isPrivileged" class="my-1" />
                    <DropMenuItem
                        v-if="carrier.status === 'archived' && isPrivileged"
                        @click="emit('unarchive', carrier)"
                    >
                        <template #leading
                            ><ArchiveRestore class="size-4"
                        /></template>
                        Unarchive
                    </DropMenuItem>
                    <DropMenuItem
                        v-else-if="
                            carrier.status !== 'archived' && isPrivileged
                        "
                        danger
                        @click="emit('archive', carrier)"
                    >
                        <template #leading><Archive class="size-4" /></template>
                        Archive
                    </DropMenuItem>
                </DropMenu>
            </div>
        </div>

        <div
            class="mt-3 flex items-center justify-between gap-3 border-t border-border-subtle pt-2.5"
        >
            <div class="flex shrink-0 items-center gap-1.5">
                <Badge tone="neutral">0 clients</Badge>
                <Badge tone="accent">0 policies</Badge>
            </div>
            <div v-if="branchCities" class="flex min-w-0 items-center gap-1.5">
                <Building2 class="size-3.5 shrink-0 text-tertiary" />
                <span class="truncate text-[12px] text-secondary">
                    {{ branchCities }}
                </span>
            </div>
        </div>
    </div>
</template>
