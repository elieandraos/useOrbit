<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronUp,
    Eye,
    MoreHorizontal,
    Pencil,
} from '@lucide/vue';
import { Avatar } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge/Badge.vue';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { Pagination } from '@/components/ui/pagination';
import {
    edit as agentsEdit,
    index as agentsIndex,
    show as agentsShow,
} from '@/routes/agents';
import type { Paginated } from '@/types';
import type { AgentResource } from './agent';
import AgentCard from './AgentCard.vue';

interface Sort {
    column: string;
    direction: 'asc' | 'desc';
}

const props = defineProps<{
    agents: Paginated<AgentResource>;
    sort: Sort;
}>();

function goToAgent(agent: AgentResource) {
    router.visit(agentsShow(agent.slug).url);
}

function sortBy(column: string) {
    const direction =
        props.sort.column === column && props.sort.direction === 'asc'
            ? 'desc'
            : 'asc';

    router.get(
        agentsIndex.url({ mergeQuery: { sort: column, direction } }),
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
            <span>{{ agents.meta.total }} agents</span>
            <span>Name {{ sort.direction === 'desc' ? 'Z→A' : 'A→Z' }}</span>
        </div>

        <!-- Mobile: stacked agent cards, replaces the table below `md` -->
        <div class="flex flex-col gap-2.5 md:hidden">
            <AgentCard
                v-for="agent in agents.data"
                :key="agent.id"
                :agent="agent"
            />
        </div>

        <!-- Mobile: pagination footer for the card list -->
        <div
            class="mt-2.5 rounded-lg border border-border bg-surface shadow-card md:hidden"
        >
            <Pagination :meta="agents.meta" item-label="agents" />
        </div>

        <!-- Desktop (`md` and above): table layout -->
        <div
            class="hidden rounded-lg border border-border bg-surface shadow-card md:block"
        >
            <div class="min-h-[488px]">
                <table class="w-full table-fixed border-collapse">
                    <colgroup>
                        <col style="width: 42%" />
                        <col style="width: 25%" />
                        <col style="width: 18%" />
                        <col class="w-[56px]" />
                    </colgroup>
                    <thead>
                        <tr class="border-b border-border bg-sunken">
                            <th
                                class="cursor-pointer rounded-tl-lg px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase select-none"
                                @click="sortBy('name')"
                            >
                                <span class="inline-flex items-center gap-1">
                                    Agent
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
                                class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                            >
                                Status
                            </th>
                            <th class="rounded-tr-lg px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(agent, index) in agents.data"
                            :key="agent.id"
                            class="cursor-pointer transition-colors hover:bg-sunken"
                            :class="
                                index !== agents.data.length - 1 &&
                                'border-b border-border-subtle'
                            "
                            @click="goToAgent(agent)"
                        >
                            <td class="min-w-0 px-4 py-3">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <Avatar
                                        :name="agent.full_name"
                                        :size="32"
                                    />
                                    <div class="min-w-0">
                                        <div
                                            class="truncate text-[13.5px] font-medium text-primary"
                                        >
                                            {{ agent.full_name }}
                                        </div>
                                        <div
                                            class="mt-0.5 truncate font-mono text-[11.5px] text-tertiary"
                                        >
                                            {{ agent.email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-[12.5px] text-secondary"
                            >
                                {{ agent.phone }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :tone="
                                        agent.status === 'active'
                                            ? 'success'
                                            : 'warning'
                                    "
                                    dot
                                >
                                    {{
                                        agent.status === 'active'
                                            ? 'Active'
                                            : 'Archived'
                                    }}
                                </Badge>
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
                                        :href="agentsShow(agent.slug).url"
                                    >
                                        <template #leading
                                            ><Eye class="size-4"
                                        /></template>
                                        View
                                    </DropMenuItem>
                                    <DropMenuItem
                                        :href="agentsEdit(agent.slug).url"
                                    >
                                        <template #leading
                                            ><Pencil class="size-4"
                                        /></template>
                                        Edit
                                    </DropMenuItem>
                                </DropMenu>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :meta="agents.meta" item-label="agents" />
        </div>
    </div>
</template>
