<script setup lang="ts">
import { Mail } from '@lucide/vue';
import { Avatar } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge/Badge.vue';
import type { OrganizationMemberResource } from './organizationMember';

defineProps<{
    members: OrganizationMemberResource[];
}>();

const statusTone: Record<
    OrganizationMemberResource['status'],
    'success' | 'warning' | 'neutral'
> = {
    active: 'success',
    invited: 'warning',
    suspended: 'neutral',
};
</script>

<template>
    <div class="rounded-lg border border-border bg-surface shadow-card">
        <!-- Mobile: stacked rows -->
        <div class="divide-y divide-border-subtle md:hidden">
            <div
                v-for="member in members"
                :key="member.id"
                class="flex items-center gap-3 px-4 py-3"
            >
                <div class="min-w-0 flex-1">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <Avatar
                            v-if="member.name"
                            :name="member.name"
                            :size="36"
                        />
                        <span
                            v-else
                            class="inline-flex size-9 shrink-0 items-center justify-center rounded-full border border-dashed border-border-strong text-tertiary"
                        >
                            <Mail class="size-4" />
                        </span>
                        <div class="min-w-0">
                            <div
                                v-if="member.name"
                                class="flex items-center gap-1.5"
                            >
                                <span
                                    class="truncate text-[13.5px] font-medium text-primary"
                                >
                                    {{ member.name }}
                                </span>
                                <span
                                    v-if="member.is_you"
                                    class="text-xs text-tertiary"
                                    >(You)</span
                                >
                            </div>
                            <div
                                v-else
                                class="truncate text-[13.5px] text-tertiary italic"
                            >
                                {{ member.email }}
                            </div>
                            <div
                                v-if="member.name"
                                class="mt-0.5 truncate text-xs text-tertiary"
                            >
                                {{ member.email }}
                            </div>
                        </div>
                    </div>
                    <div class="mt-2.5 flex gap-1.5 pl-[46px]">
                        <Badge tone="neutral" class="capitalize">{{
                            member.role
                        }}</Badge>
                        <Badge
                            :tone="statusTone[member.status]"
                            dot
                            class="capitalize"
                            >{{ member.status }}</Badge
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop: table -->
        <table class="hidden w-full table-fixed border-collapse md:table">
            <colgroup>
                <col style="width: 55%" />
                <col style="width: 22%" />
                <col style="width: 23%" />
            </colgroup>
            <thead>
                <tr class="border-b border-border bg-sunken">
                    <th
                        class="rounded-tl-lg px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                    >
                        Member
                    </th>
                    <th
                        class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                    >
                        Role
                    </th>
                    <th
                        class="rounded-tr-lg px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                    >
                        Status
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(member, index) in members"
                    :key="member.id"
                    :class="
                        index !== members.length - 1 &&
                        'border-b border-border-subtle'
                    "
                >
                    <td class="min-w-0 px-4 py-3">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <Avatar
                                v-if="member.name"
                                :name="member.name"
                                :size="32"
                            />
                            <span
                                v-else
                                class="inline-flex size-8 shrink-0 items-center justify-center rounded-full border border-dashed border-border-strong text-tertiary"
                            >
                                <Mail class="size-4" />
                            </span>
                            <div class="min-w-0">
                                <div
                                    v-if="member.name"
                                    class="flex items-center gap-1.5"
                                >
                                    <span
                                        class="truncate text-[13.5px] font-medium text-primary"
                                    >
                                        {{ member.name }}
                                    </span>
                                    <span
                                        v-if="member.is_you"
                                        class="text-xs text-tertiary"
                                        >(You)</span
                                    >
                                </div>
                                <div
                                    v-else
                                    class="truncate text-[13.5px] text-tertiary italic"
                                >
                                    {{ member.email }}
                                </div>
                                <div
                                    v-if="member.name"
                                    class="mt-0.5 truncate font-mono text-[11.5px] text-tertiary"
                                >
                                    {{ member.email }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <Badge tone="neutral" class="capitalize">{{
                            member.role
                        }}</Badge>
                    </td>
                    <td class="px-4 py-3">
                        <Badge
                            :tone="statusTone[member.status]"
                            dot
                            class="capitalize"
                            >{{ member.status }}</Badge
                        >
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
