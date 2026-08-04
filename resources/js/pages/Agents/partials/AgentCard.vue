<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Eye, MoreHorizontal, Pencil } from '@lucide/vue';
import { Avatar } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge/Badge.vue';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { edit as agentsEdit, show as agentsShow } from '@/routes/agents';
import type { AgentResource } from './agent';

defineProps<{
    agent: AgentResource;
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
                </DropMenu>
            </div>
        </div>

        <div
            class="mt-3 flex items-center justify-between gap-3 border-t border-border-subtle pt-2.5"
        >
            <span class="font-mono text-[12px] text-secondary">{{
                agent.phone
            }}</span>
            <Badge
                :tone="agent.status === 'active' ? 'success' : 'warning'"
                dot
            >
                {{ agent.status === 'active' ? 'Active' : 'Archived' }}
            </Badge>
        </div>
    </div>
</template>
