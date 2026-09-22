<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { MoreHorizontal, Pencil } from '@lucide/vue';
import { Avatar } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge/Badge.vue';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { policyStatusTone } from '@/lib/policyStatusTone';
import type { PolicyResource } from '@/types/policy';

const props = defineProps<{
    policy: PolicyResource;
    classColor: string;
    showUrl: string;
    editUrl: string;
}>();

function goToPolicy() {
    router.visit(props.showUrl);
}
</script>

<template>
    <!-- Rendered only below `md` by PoliciesTable.vue, in place of a table row -->
    <div
        class="cursor-pointer rounded-lg border border-border bg-surface p-3.5 shadow-card transition-colors active:bg-sunken"
        @click="goToPolicy"
    >
        <div class="flex items-start gap-3">
            <div
                class="flex size-9 shrink-0 items-center justify-center rounded-lg border font-mono text-[10.5px] font-bold tracking-wider uppercase"
                :style="{
                    backgroundColor: `${classColor}15`,
                    borderColor: `${classColor}30`,
                    color: classColor,
                }"
            >
                {{ policy.class_label.slice(0, 3) }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="truncate text-[14px] font-medium text-primary">
                    {{ policy.policy_number }}
                </div>
                <div
                    class="mt-0.5 truncate font-mono text-[11.5px] text-tertiary"
                >
                    {{ policy.subclass }} · {{ policy.carrier.name }}
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

                    <DropMenuItem :href="editUrl">
                        <template #leading><Pencil class="size-4" /></template>
                        Edit
                    </DropMenuItem>
                </DropMenu>
            </div>
        </div>

        <div
            class="mt-3 flex flex-col gap-1.5 border-t border-border-subtle pt-2.5"
        >
            <div class="flex min-w-0 items-center justify-between gap-2">
                <div class="flex min-w-0 items-center gap-2">
                    <Avatar :name="policy.client.full_name" :size="20" />
                    <span class="truncate text-[13px] text-primary">
                        {{ policy.client.full_name }}
                    </span>
                </div>
                <Badge :tone="policyStatusTone[policy.status] ?? 'neutral'" dot>
                    {{ policy.status_label }}
                </Badge>
            </div>
            <div
                class="flex min-w-0 items-center gap-2 font-mono text-[11.5px] text-tertiary"
            >
                {{ policy.effective_date_formatted }} →
                {{ policy.expiry_date_formatted }}
            </div>
            <div class="flex min-w-0 items-center justify-between gap-2">
                <Badge :tone="policy.type === 'group' ? 'accent' : 'neutral'">
                    {{ policy.type_label }}
                </Badge>
                <span class="font-mono text-[13px] font-medium text-primary">
                    {{ policy.net_premium }}
                </span>
            </div>
        </div>
    </div>
</template>
