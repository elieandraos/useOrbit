<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Archive, Eye, MoreHorizontal, Pencil } from '@lucide/vue';
import { Avatar } from '@/components/ui/avatar';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { Pagination } from '@/components/ui/pagination';
import { Separator } from '@/components/ui/separator';
import { edit as clientsEdit, show as clientsShow } from '@/routes/clients';
import type { Paginated } from '@/types';
import type { ClientResource } from './client';

defineProps<{
    clients: Paginated<ClientResource>;
}>();

function goToClient(client: ClientResource) {
    router.visit(clientsShow(client.slug).url);
}
</script>

<template>
    <div class="rounded-lg border border-border bg-surface shadow-card">
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
                            class="rounded-tl-lg px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                        >
                            Client name
                        </th>
                        <th
                            class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                        >
                            Phone
                        </th>
                        <th
                            class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                        >
                            Email
                        </th>
                        <th
                            class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                        >
                            Enrollment date
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
                                <Avatar :name="client.full_name" :size="30" />
                                <div class="min-w-0">
                                    <div
                                        class="truncate text-[13.5px] font-medium text-primary"
                                    >
                                        {{ client.full_name }}
                                    </div>
                                    <div
                                        class="mt-0.5 font-mono text-[11.5px] text-tertiary"
                                    >
                                        {{ client.lead_source_label }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td
                            class="px-4 py-3 font-mono text-[12.5px] text-secondary"
                        >
                            {{ client.phone }}
                        </td>
                        <td
                            class="truncate px-4 py-3 text-[13px] text-secondary"
                        >
                            {{ client.email }}
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
                                            <MoreHorizontal class="size-4" />
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
                                    <DropMenuItem
                                        danger
                                        disabled
                                        class="opacity-50"
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
</template>
