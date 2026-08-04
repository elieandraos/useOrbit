<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Archive,
    ArchiveRestore,
    Eye,
    MoreHorizontal,
    Pencil,
    Phone,
} from '@lucide/vue';
import { Avatar } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge/Badge.vue';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { Separator } from '@/components/ui/separator';
import { edit as agentsEdit, show as agentsShow } from '@/routes/agents';
import type { AgentResource } from './agent';

defineProps<{
    agent: AgentResource;
}>();

const emit = defineEmits<{
    archive: [agent: AgentResource];
    unarchive: [agent: AgentResource];
}>();

function goToAgent(agent: AgentResource) {
    router.visit(agentsShow(agent.slug).url);
}
</script>

<template>
    <!-- Rendered only below `md` by AgentsTable.vue, in place of a table row -->
    <div
        class="cursor-pointer rounded-lg border border-border bg-surface p-3.5 shadow-card transition-colors active:bg-sunken"
        @click="goToAgent(agent)"
    >
        <div class="flex items-start gap-3">
            <Avatar :name="agent.full_name" :size="36" />
            <div class="min-w-0 flex-1">
                <div class="truncate text-[14px] font-medium text-primary">
                    {{ agent.full_name }}
                </div>
                <div
                    class="mt-0.5 truncate font-mono text-[11.5px] text-tertiary"
                >
                    {{ agent.email }}
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

                    <DropMenuItem :href="agentsShow(agent.slug).url">
                        <template #leading><Eye class="size-4" /></template>
                        View
                    </DropMenuItem>
                    <DropMenuItem :href="agentsEdit(agent.slug).url">
                        <template #leading><Pencil class="size-4" /></template>
                        Edit
                    </DropMenuItem>
                    <Separator class="my-1" />
                    <DropMenuItem
                        v-if="agent.status === 'archived'"
                        @click="emit('unarchive', agent)"
                    >
                        <template #leading
                            ><ArchiveRestore class="size-4"
                        /></template>
                        Unarchive
                    </DropMenuItem>
                    <DropMenuItem v-else danger @click="emit('archive', agent)">
                        <template #leading><Archive class="size-4" /></template>
                        Archive
                    </DropMenuItem>
                </DropMenu>
            </div>
        </div>

        <div
            class="mt-3 flex flex-col gap-1.5 border-t border-border-subtle pt-2.5"
        >
            <div class="flex min-w-0 items-center gap-2">
                <Phone class="size-3.5 shrink-0 text-tertiary" />
                <span class="truncate font-mono text-[12.5px] text-secondary">
                    {{ agent.phone }}
                </span>
            </div>
            <div class="flex items-center justify-between gap-3">
                <div class="flex shrink-0 items-center gap-1.5">
                    <Badge tone="neutral">0 clients</Badge>
                    <Badge tone="accent">0 policies</Badge>
                </div>
                <Badge
                    :tone="agent.status === 'active' ? 'success' : 'warning'"
                    dot
                >
                    {{ agent.status === 'active' ? 'Active' : 'Archived' }}
                </Badge>
            </div>
        </div>
    </div>
</template>
