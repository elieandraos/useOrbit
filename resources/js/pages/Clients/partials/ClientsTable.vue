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
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import { Avatar } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge/Badge.vue';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { Pagination } from '@/components/ui/pagination';
import { Separator } from '@/components/ui/separator';
import {
    edit as clientsEdit,
    index as clientsIndex,
    show as clientsShow,
    unarchive as clientsUnarchive,
} from '@/routes/clients';
import type { Paginated } from '@/types';
import ArchiveClientModal from './ArchiveClientModal.vue';
import type { ClientResource } from './client';
import ClientCard from './ClientCard.vue';
import DeleteClientModal from './DeleteClientModal.vue';

interface Sort {
    column: string;
    direction: 'asc' | 'desc';
}

const props = defineProps<{
    clients: Paginated<ClientResource>;
    sort: Sort;
    sortLabel: string;
}>();

const clientToArchive = ref<ClientResource | null>(null);
const clientToDelete = ref<ClientResource | null>(null);

function goToClient(client: ClientResource) {
    router.visit(clientsShow(client.slug).url);
}

function unarchiveClient(client: ClientResource) {
    router.patch(
        clientsUnarchive.url(client.slug),
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
        clientsIndex.url({ mergeQuery: { sort: column, direction } }),
        {},
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <div>
        <!-- Mobile: count + sort caption, above the card list -->
        <div
            class="mb-2.5 flex items-center justify-between font-mono text-[11px] tracking-wider text-tertiary uppercase md:hidden"
        >
            <span>{{ clients.meta.total }} clients</span>
            <span>{{ sortLabel }} ↓</span>
        </div>

        <!-- Mobile: stacked client cards, replaces the table below `md` -->
        <div class="flex flex-col gap-2.5 md:hidden">
            <ClientCard
                v-for="client in clients.data"
                :key="client.id"
                :client="client"
                @archive="clientToArchive = $event"
                @unarchive="unarchiveClient"
                @delete="clientToDelete = $event"
            />
        </div>

        <!-- Mobile: pagination footer for the card list -->
        <div
            class="mt-2.5 rounded-lg border border-border bg-surface shadow-card md:hidden"
        >
            <Pagination :meta="clients.meta" item-label="clients" />
        </div>

        <!-- Desktop (`md` and above): table layout -->
        <div
            class="hidden rounded-lg border border-border bg-surface shadow-card md:block"
        >
            <div class="min-h-[488px]">
                <table class="w-full table-fixed border-collapse">
                    <colgroup>
                        <col style="width: 28%" />
                        <col style="width: 17%" />
                        <col style="width: 25%" />
                        <col style="width: 17%" />
                        <col class="w-[60px]" />
                    </colgroup>
                    <thead>
                        <tr class="border-b border-border bg-sunken">
                            <th
                                class="cursor-pointer rounded-tl-lg px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase select-none"
                                @click="sortBy('name')"
                            >
                                <span class="inline-flex items-center gap-1">
                                    Client name
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
                                class="cursor-pointer px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase select-none"
                                @click="sortBy('type')"
                            >
                                <span class="inline-flex items-center gap-1">
                                    Type
                                    <ChevronUp
                                        v-if="
                                            sort.column === 'type' &&
                                            sort.direction === 'asc'
                                        "
                                        class="size-3"
                                    />
                                    <ChevronDown
                                        v-else-if="sort.column === 'type'"
                                        class="size-3"
                                    />
                                </span>
                            </th>
                            <th
                                class="cursor-pointer px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase select-none"
                                @click="sortBy('enrollment_date')"
                            >
                                <span class="inline-flex items-center gap-1">
                                    Enrollment date
                                    <ChevronUp
                                        v-if="
                                            sort.column === 'enrollment_date' &&
                                            sort.direction === 'asc'
                                        "
                                        class="size-3"
                                    />
                                    <ChevronDown
                                        v-else-if="
                                            sort.column === 'enrollment_date'
                                        "
                                        class="size-3"
                                    />
                                </span>
                            </th>
                            <th class="rounded-tr-lg px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(client, index) in clients.data"
                            :key="client.id"
                            class="cursor-pointer transition-colors hover:bg-sunken"
                            :class="
                                index !== clients.data.length - 1 &&
                                'border-b border-border-subtle'
                            "
                            @click="goToClient(client)"
                        >
                            <td class="min-w-0 px-4 py-3">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <Avatar
                                        :name="client.full_name"
                                        :size="30"
                                    />
                                    <div class="min-w-0">
                                        <div
                                            class="truncate text-[13.5px] font-medium text-primary"
                                        >
                                            {{ client.full_name }}
                                        </div>
                                        <div
                                            v-if="
                                                client.client_type ===
                                                'individual'
                                            "
                                            class="mt-0.5 font-mono text-[11.5px] text-tertiary"
                                        >
                                            {{ client.age }} yrs
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-[12.5px] text-secondary"
                            >
                                {{ client.phone }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :tone="
                                        client.client_type === 'company'
                                            ? 'accent'
                                            : 'neutral'
                                    "
                                    dot
                                >
                                    {{ client.client_type_label }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-[13px] text-primary">
                                {{ client.enrollment_date_formatted }}
                            </td>
                            <td class="px-4 py-3 text-right" @click.stop>
                                <div class="inline-flex items-center gap-0.5">
                                    <DropMenu>
                                        <template #trigger>
                                            <button
                                                class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md text-secondary transition-colors hover:bg-sunken"
                                            >
                                                <MoreHorizontal
                                                    class="size-4"
                                                />
                                            </button>
                                        </template>

                                        <DropMenuItem
                                            :href="clientsShow(client.slug).url"
                                        >
                                            <template #leading
                                                ><Eye class="size-4"
                                            /></template>
                                            View
                                        </DropMenuItem>
                                        <DropMenuItem
                                            :href="clientsEdit(client.slug).url"
                                        >
                                            <template #leading
                                                ><Pencil class="size-4"
                                            /></template>
                                            Edit
                                        </DropMenuItem>
                                        <Separator class="my-1" />
                                        <template
                                            v-if="client.status === 'archived'"
                                        >
                                            <DropMenuItem
                                                @click="unarchiveClient(client)"
                                            >
                                                <template #leading
                                                    ><ArchiveRestore
                                                        class="size-4"
                                                /></template>
                                                Unarchive
                                            </DropMenuItem>
                                            <DropMenuItem
                                                danger
                                                @click="clientToDelete = client"
                                            >
                                                <template #leading
                                                    ><Trash2 class="size-4"
                                                /></template>
                                                Delete permanently
                                            </DropMenuItem>
                                        </template>
                                        <DropMenuItem
                                            v-else
                                            danger
                                            @click="clientToArchive = client"
                                        >
                                            <template #leading
                                                ><Archive class="size-4"
                                            /></template>
                                            Archive
                                        </DropMenuItem>
                                    </DropMenu>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :meta="clients.meta" item-label="clients" />
        </div>

        <ArchiveClientModal v-model="clientToArchive" />
        <DeleteClientModal v-model="clientToDelete" />
    </div>
</template>
