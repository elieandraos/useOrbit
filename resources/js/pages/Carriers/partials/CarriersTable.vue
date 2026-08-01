<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Archive, Eye, MoreHorizontal, Pencil, Plus } from '@lucide/vue';
import { ref } from 'vue';
import { Avatar } from '@/components/ui/avatar';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { Pagination } from '@/components/ui/pagination';
import { Separator } from '@/components/ui/separator';
import { edit as carriersEdit, show as carriersShow } from '@/routes/carriers';
import type { Paginated } from '@/types';
import ArchiveCarrierModal from './ArchiveCarrierModal.vue';
import type { CarrierResource } from './carrier';
import CarrierCard from './CarrierCard.vue';

defineProps<{
    carriers: Paginated<CarrierResource>;
}>();

const carrierToArchive = ref<CarrierResource | null>(null);

function goToCarrier(carrier: CarrierResource) {
    router.visit(carriersShow(carrier.slug).url);
}
</script>

<template>
    <div>
        <!-- Mobile: count caption above the card list -->
        <div
            class="mb-2.5 flex items-center justify-between font-mono text-[11px] tracking-wider text-tertiary uppercase md:hidden"
        >
            <span>{{ carriers.meta.total }} carriers</span>
            <span>Name A→Z</span>
        </div>

        <!-- Mobile: stacked carrier cards, replaces the table below `md` -->
        <div class="flex flex-col gap-2.5 md:hidden">
            <CarrierCard
                v-for="carrier in carriers.data"
                :key="carrier.id"
                :carrier="carrier"
                @archive="carrierToArchive = $event"
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
                        <col style="width: 36%" />
                        <col style="width: 20%" />
                        <col style="width: 18%" />
                        <col style="width: 11%" />
                        <col style="width: 11%" />
                        <col class="w-[56px]" />
                    </colgroup>
                    <thead>
                        <tr class="border-b border-border bg-sunken">
                            <th
                                class="rounded-tl-lg px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                            >
                                Carrier
                            </th>
                            <th
                                class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                            >
                                Primary contact
                            </th>
                            <th
                                class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                            >
                                HQ
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
                            <th
                                class="rounded-tr-lg px-4 py-2.5 text-right font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                            >
                                Actions
                            </th>
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
                            <td class="min-w-0 px-4 py-3">
                                <div
                                    class="truncate text-[13px] font-medium text-primary"
                                >
                                    {{ carrier.branch?.contact_name ?? '—' }}
                                </div>
                                <div
                                    v-if="carrier.branch?.contact_role"
                                    class="mt-0.5 truncate text-[11.5px] text-tertiary"
                                >
                                    {{ carrier.branch.contact_role }}
                                </div>
                            </td>
                            <td
                                class="truncate px-4 py-3 text-[13px] text-secondary"
                            >
                                {{ carrier.branch?.city ?? '—' }}
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
                                        View carrier
                                    </DropMenuItem>
                                    <DropMenuItem
                                        :href="carriersEdit(carrier.slug).url"
                                    >
                                        <template #leading
                                            ><Pencil class="size-4"
                                        /></template>
                                        Edit
                                    </DropMenuItem>
                                    <DropMenuItem
                                        disabled
                                        class="pointer-events-none opacity-50"
                                    >
                                        <template #leading
                                            ><Plus class="size-4"
                                        /></template>
                                        Add policy
                                    </DropMenuItem>
                                    <Separator class="my-1" />
                                    <DropMenuItem
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
