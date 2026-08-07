<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Archive,
    ArchiveRestore,
    ChevronDown,
    ChevronUp,
    Eye,
    MoreHorizontal,
    Pencil,
} from '@lucide/vue';
import { ref } from 'vue';
import { Avatar } from '@/components/ui/avatar';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { Pagination } from '@/components/ui/pagination';
import { Separator } from '@/components/ui/separator';
import { useAuth } from '@/composables/useAuth';
import {
    edit as carriersEdit,
    index as carriersIndex,
    show as carriersShow,
    unarchive as carriersUnarchive,
} from '@/routes/carriers';
import type { Paginated } from '@/types';
import ArchiveCarrierModal from './ArchiveCarrierModal.vue';
import type { CarrierResource } from './carrier';
import CarrierCard from './CarrierCard.vue';

interface Sort {
    column: string;
    direction: 'asc' | 'desc';
}

const props = defineProps<{
    carriers: Paginated<CarrierResource>;
    sort: Sort;
}>();

const { isPrivileged } = useAuth();

const carrierToArchive = ref<CarrierResource | null>(null);

function goToCarrier(carrier: CarrierResource) {
    router.visit(carriersShow(carrier.slug).url);
}

function unarchiveCarrier(carrier: CarrierResource) {
    router.patch(
        carriersUnarchive.url(carrier.slug),
        {},
        { preserveScroll: true },
    );
}

function sortBy(column: string) {
    const direction =
        props.sort.column === column && props.sort.direction === 'asc'
            ? 'desc'
            : 'asc';

    router.get(
        carriersIndex.url({ mergeQuery: { sort: column, direction } }),
        {},
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <div>
        <!-- Mobile: count caption above the card list -->
        <div
            class="mb-2.5 flex items-center justify-between font-mono text-[11px] tracking-wider text-tertiary uppercase md:hidden"
        >
            <span>{{ carriers.meta.total }} carriers</span>
            <span>Name {{ sort.direction === 'desc' ? 'Z→A' : 'A→Z' }}</span>
        </div>

        <!-- Mobile: stacked carrier cards, replaces the table below `md` -->
        <div class="flex flex-col gap-2.5 md:hidden">
            <CarrierCard
                v-for="carrier in carriers.data"
                :key="carrier.id"
                :carrier="carrier"
                @archive="carrierToArchive = $event"
                @unarchive="unarchiveCarrier"
            />
        </div>

        <!-- Mobile: pagination footer for the card list -->
        <div
            class="mt-2.5 rounded-lg border border-border bg-surface shadow-card md:hidden"
        >
            <Pagination :meta="carriers.meta" item-label="carriers" />
        </div>

        <!-- Desktop (`md` and above): table layout -->
        <div
            class="hidden rounded-lg border border-border bg-surface shadow-card md:block"
        >
            <div class="min-h-[488px]">
                <table class="w-full table-fixed border-collapse">
                    <colgroup>
                        <col style="width: 38%" />
                        <col style="width: 24%" />
                        <col style="width: 15%" />
                        <col style="width: 15%" />
                        <col class="w-[56px]" />
                    </colgroup>
                    <thead>
                        <tr class="border-b border-border bg-sunken">
                            <th
                                class="cursor-pointer rounded-tl-lg px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase select-none"
                                @click="sortBy('name')"
                            >
                                <span class="inline-flex items-center gap-1">
                                    Name
                                    <ChevronUp
                                        v-if="
                                            sort.column === 'name' &&
                                            sort.direction === 'asc'
                                        "
                                        class="size-3"
                                    />
                                    <ChevronDown
                                        v-else-if="sort.column === 'name'"
                                        class="size-3"
                                    />
                                </span>
                            </th>
                            <th
                                class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                            >
                                Phone
                            </th>
                            <th
                                class="px-4 py-2.5 text-right font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                            >
                                Clients
                            </th>
                            <th
                                class="px-4 py-2.5 text-right font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                            >
                                Policies
                            </th>
                            <th class="rounded-tr-lg px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(carrier, index) in carriers.data"
                            :key="carrier.id"
                            class="cursor-pointer transition-colors hover:bg-sunken"
                            :class="
                                index !== carriers.data.length - 1 &&
                                'border-b border-border-subtle'
                            "
                            @click="goToCarrier(carrier)"
                        >
                            <td class="min-w-0 px-4 py-3">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <Avatar :name="carrier.name" :size="32" />
                                    <div class="min-w-0">
                                        <div
                                            class="truncate text-[13.5px] font-medium text-primary"
                                        >
                                            {{ carrier.name }}
                                        </div>
                                        <div
                                            v-if="carrier.website"
                                            class="mt-0.5 truncate font-mono text-[11.5px] text-tertiary"
                                        >
                                            {{ carrier.website }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-[12.5px] text-secondary"
                            >
                                {{ carrier.phone ?? '—' }}
                            </td>
                            <td
                                class="px-4 py-3 text-right font-mono text-[13px] text-primary"
                            >
                                0
                            </td>
                            <td
                                class="px-4 py-3 text-right font-mono text-[13px] text-primary"
                            >
                                0
                            </td>
                            <td class="px-4 py-3 text-right" @click.stop>
                                <DropMenu>
                                    <template #trigger>
                                        <button
                                            class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md text-secondary transition-colors hover:bg-sunken"
                                        >
                                            <MoreHorizontal class="size-4" />
                                        </button>
                                    </template>

                                    <DropMenuItem
                                        :href="carriersShow(carrier.slug).url"
                                    >
                                        <template #leading
                                            ><Eye class="size-4"
                                        /></template>
                                        View
                                    </DropMenuItem>
                                    <DropMenuItem
                                        :href="carriersEdit(carrier.slug).url"
                                    >
                                        <template #leading
                                            ><Pencil class="size-4"
                                        /></template>
                                        Edit
                                    </DropMenuItem>
                                    <Separator
                                        v-if="isPrivileged"
                                        class="my-1"
                                    />
                                    <DropMenuItem
                                        v-if="
                                            carrier.status === 'archived' &&
                                            isPrivileged
                                        "
                                        @click="unarchiveCarrier(carrier)"
                                    >
                                        <template #leading
                                            ><ArchiveRestore class="size-4"
                                        /></template>
                                        Unarchive
                                    </DropMenuItem>
                                    <DropMenuItem
                                        v-else-if="
                                            carrier.status !== 'archived' &&
                                            isPrivileged
                                        "
                                        danger
                                        @click="carrierToArchive = carrier"
                                    >
                                        <template #leading
                                            ><Archive class="size-4"
                                        /></template>
                                        Archive
                                    </DropMenuItem>
                                </DropMenu>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :meta="carriers.meta" item-label="carriers" />
        </div>

        <ArchiveCarrierModal v-model="carrierToArchive" />
    </div>
</template>
