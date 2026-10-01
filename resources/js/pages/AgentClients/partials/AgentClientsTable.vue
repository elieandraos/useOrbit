<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Avatar } from '@/components/ui/avatar';
import { Pagination } from '@/components/ui/pagination';
import { show as clientsShow } from '@/routes/clients';
import type { Paginated } from '@/types';
import type { ClientResource } from '../../Clients/partials/client';

defineProps<{
    clients: Paginated<ClientResource>;
}>();

function goToClient(client: ClientResource) {
    router.visit(clientsShow(client.slug).url);
}
</script>

<template>
    <div>
        <!-- Mobile: count caption above the card list -->
        <div
            class="mb-2.5 flex items-center justify-between font-mono text-[11px] tracking-wider text-tertiary uppercase md:hidden"
        >
            <span>{{ clients.meta.total }} clients</span>
            <span>Name A→Z</span>
        </div>

        <!-- Mobile: stacked client cards, replaces the table below `md` -->
        <div class="flex flex-col gap-2.5 md:hidden">
            <div
                v-for="client in clients.data"
                :key="client.id"
                class="cursor-pointer rounded-lg border border-border bg-surface p-3.5 shadow-card transition-colors active:bg-sunken"
                @click="goToClient(client)"
            >
                <div class="flex items-start gap-3">
                    <Avatar :name="client.full_name" :size="36" />
                    <div class="min-w-0 flex-1">
                        <div
                            class="truncate text-[14px] font-medium text-primary"
                        >
                            {{ client.full_name }}
                        </div>
                        <div
                            class="mt-0.5 font-mono text-[11.5px] text-tertiary"
                        >
                            {{ client.client_type_label }}
                        </div>
                    </div>
                </div>
                <div
                    class="mt-3 flex items-center justify-between gap-3 border-t border-border-subtle pt-2.5 font-mono text-[12px] text-secondary"
                >
                    <span class="truncate">{{ client.phone }}</span>
                    <span class="shrink-0">
                        {{ client.enrollment_date_formatted }}
                    </span>
                </div>
            </div>
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
                        <col style="width: 46%" />
                        <col style="width: 27%" />
                        <col style="width: 27%" />
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
                                class="rounded-tr-lg px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                            >
                                Enrollment date
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
                                            class="mt-0.5 font-mono text-[11.5px] text-tertiary"
                                        >
                                            {{ client.client_type_label }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-[12.5px] text-secondary"
                            >
                                {{ client.phone }}
                            </td>
                            <td class="px-4 py-3 text-[13px] text-primary">
                                {{ client.enrollment_date_formatted }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :meta="clients.meta" item-label="clients" />
        </div>
    </div>
</template>
