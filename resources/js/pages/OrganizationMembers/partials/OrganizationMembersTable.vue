<script setup lang="ts">
import {
    ArrowLeftRight,
    Ban,
    Mail,
    MoreHorizontal,
    ShieldOff,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import { Avatar } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge/Badge.vue';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { Separator } from '@/components/ui/separator';
import ChangeRoleModal from './ChangeRoleModal.vue';
import type {
    InvitableRoleOption,
    OrganizationMemberResource,
} from './organizationMember';
import RemoveMemberModal from './RemoveMemberModal.vue';
import ResetTwoFactorModal from './ResetTwoFactorModal.vue';
import RevokeInvitationModal from './RevokeInvitationModal.vue';

defineProps<{
    members: OrganizationMemberResource[];
    roleOptions: InvitableRoleOption[];
}>();

const statusTone: Record<
    OrganizationMemberResource['status'],
    'success' | 'warning' | 'neutral'
> = {
    active: 'success',
    invited: 'warning',
    suspended: 'neutral',
};

const memberToChangeRole = ref<OrganizationMemberResource | null>(null);
const memberToRemove = ref<OrganizationMemberResource | null>(null);
const memberToRevoke = ref<OrganizationMemberResource | null>(null);
const memberToResetTwoFactor = ref<OrganizationMemberResource | null>(null);

function canManageMember(member: OrganizationMemberResource): boolean {
    return member.status === 'invited'
        ? member.can_revoke
        : member.can_change_role ||
              member.can_remove ||
              member.can_reset_two_factor;
}
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
                    <div class="mt-2.5 flex items-center gap-1.5 pl-[46px]">
                        <Badge tone="neutral" class="capitalize">{{
                            member.role
                        }}</Badge>
                        <Badge
                            :tone="statusTone[member.status]"
                            dot
                            class="capitalize"
                            >{{ member.status }}</Badge
                        >
                        <span class="text-xs text-tertiary">
                            {{ member.last_login_at ?? 'Never logged in' }}
                        </span>
                    </div>
                </div>

                <DropMenu v-if="canManageMember(member)">
                    <template #trigger>
                        <button
                            class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md text-secondary transition-colors hover:bg-sunken"
                        >
                            <MoreHorizontal class="size-4" />
                        </button>
                    </template>

                    <DropMenuItem
                        v-if="member.status === 'invited'"
                        danger
                        @click="memberToRevoke = member"
                    >
                        <template #leading><Ban class="size-4" /></template>
                        Revoke Invitation
                    </DropMenuItem>
                    <template v-else>
                        <DropMenuItem
                            v-if="member.can_change_role"
                            @click="memberToChangeRole = member"
                        >
                            <template #leading
                                ><ArrowLeftRight class="size-4"
                            /></template>
                            Change Role
                        </DropMenuItem>
                        <Separator
                            v-if="
                                member.can_change_role &&
                                (member.can_reset_two_factor ||
                                    member.can_remove)
                            "
                            class="my-1"
                        />
                        <DropMenuItem
                            v-if="member.can_reset_two_factor"
                            danger
                            @click="memberToResetTwoFactor = member"
                        >
                            <template #leading
                                ><ShieldOff class="size-4"
                            /></template>
                            Reset 2FA
                        </DropMenuItem>
                        <Separator
                            v-if="
                                member.can_reset_two_factor && member.can_remove
                            "
                            class="my-1"
                        />
                        <DropMenuItem
                            v-if="member.can_remove"
                            danger
                            @click="memberToRemove = member"
                        >
                            <template #leading
                                ><Trash2 class="size-4"
                            /></template>
                            Remove Member
                        </DropMenuItem>
                    </template>
                </DropMenu>
                <span v-else class="size-7 shrink-0" />
            </div>
        </div>

        <!-- Desktop: table -->
        <table class="hidden w-full table-fixed border-collapse md:table">
            <colgroup>
                <col style="width: 38%" />
                <col style="width: 15%" />
                <col style="width: 15%" />
                <col style="width: 22%" />
                <col class="w-[56px]" />
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
                        class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                    >
                        Status
                    </th>
                    <th
                        class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                    >
                        Last Login
                    </th>
                    <th class="rounded-tr-lg px-4 py-2.5"></th>
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
                    <td class="px-4 py-3 text-xs text-tertiary">
                        {{ member.last_login_at ?? 'Never logged in' }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <DropMenu v-if="canManageMember(member)">
                            <template #trigger>
                                <button
                                    class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md text-secondary transition-colors hover:bg-sunken"
                                >
                                    <MoreHorizontal class="size-4" />
                                </button>
                            </template>

                            <DropMenuItem
                                v-if="member.status === 'invited'"
                                danger
                                @click="memberToRevoke = member"
                            >
                                <template #leading
                                    ><Ban class="size-4"
                                /></template>
                                Revoke Invitation
                            </DropMenuItem>
                            <template v-else>
                                <DropMenuItem
                                    v-if="member.can_change_role"
                                    @click="memberToChangeRole = member"
                                >
                                    <template #leading
                                        ><ArrowLeftRight class="size-4"
                                    /></template>
                                    Change Role
                                </DropMenuItem>
                                <Separator
                                    v-if="
                                        member.can_change_role &&
                                        (member.can_reset_two_factor ||
                                            member.can_remove)
                                    "
                                    class="my-1"
                                />
                                <DropMenuItem
                                    v-if="member.can_reset_two_factor"
                                    danger
                                    @click="memberToResetTwoFactor = member"
                                >
                                    <template #leading
                                        ><ShieldOff class="size-4"
                                    /></template>
                                    Reset 2FA
                                </DropMenuItem>
                                <Separator
                                    v-if="
                                        member.can_reset_two_factor &&
                                        member.can_remove
                                    "
                                    class="my-1"
                                />
                                <DropMenuItem
                                    v-if="member.can_remove"
                                    danger
                                    @click="memberToRemove = member"
                                >
                                    <template #leading
                                        ><Trash2 class="size-4"
                                    /></template>
                                    Remove Member
                                </DropMenuItem>
                            </template>
                        </DropMenu>
                        <span v-else class="text-xs text-tertiary">—</span>
                    </td>
                </tr>
            </tbody>
        </table>

        <ChangeRoleModal
            v-model="memberToChangeRole"
            :role-options="roleOptions"
        />
        <RemoveMemberModal v-model="memberToRemove" :members="members" />
        <RevokeInvitationModal v-model="memberToRevoke" />
        <ResetTwoFactorModal v-model="memberToResetTwoFactor" />
    </div>
</template>
