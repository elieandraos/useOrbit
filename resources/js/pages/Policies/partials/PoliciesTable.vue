<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { MoreHorizontal, Pencil } from '@lucide/vue';
import { Avatar } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge/Badge.vue';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { Pagination } from '@/components/ui/pagination';
import { policyStatusTone } from '@/lib/policyStatusTone';
import {
    edit as automotiveEdit,
    show as automotiveShow,
} from '@/routes/policies/automotive';
import { edit as expatEdit, show as expatShow } from '@/routes/policies/expat';
import { edit as fireEdit, show as fireShow } from '@/routes/policies/fire';
import { edit as lifeEdit, show as lifeShow } from '@/routes/policies/life';
import {
    edit as medicalEdit,
    show as medicalShow,
} from '@/routes/policies/medical';
import {
    edit as travelEdit,
    show as travelShow,
} from '@/routes/policies/travel';
import type { Paginated } from '@/types';
import type { PolicyResource } from '@/types/policy';
import PolicyCard from './PolicyCard.vue';

defineProps<{
    policies: Paginated<PolicyResource>;
}>();

const classColors: Record<string, string> = {
    medical: '#0369a1',
    automotive: '#b45309',
    expat: '#7c3aed',
    life: '#15803d',
    fire: '#b91c1c',
    travel: '#0d9488',
};

const classRoutes: Record<
    string,
    {
        show: (slug: string) => { url: string };
        edit: (slug: string) => { url: string };
    }
> = {
    medical: { show: medicalShow, edit: medicalEdit },
    automotive: { show: automotiveShow, edit: automotiveEdit },
    expat: { show: expatShow, edit: expatEdit },
    fire: { show: fireShow, edit: fireEdit },
    life: { show: lifeShow, edit: lifeEdit },
    travel: { show: travelShow, edit: travelEdit },
};

function classColor(policy: PolicyResource): string {
    return classColors[policy.class] ?? '#52525b';
}

function goToPolicy(policy: PolicyResource) {
    router.visit(classRoutes[policy.class].show(policy.slug).url);
}
</script>

<template>
    <div>
        <!-- Mobile: count caption above the card list -->
        <div
            class="mb-2.5 flex items-center justify-between font-mono text-[11px] tracking-wider text-tertiary uppercase md:hidden"
        >
            <span>{{ policies.meta.total }} policies</span>
        </div>

        <!-- Mobile: stacked policy cards, replaces the table below `md` -->
        <div class="flex flex-col gap-2.5 md:hidden">
            <PolicyCard
                v-for="policy in policies.data"
                :key="policy.id"
                :policy="policy"
                :class-color="classColor(policy)"
                :show-url="classRoutes[policy.class].show(policy.slug).url"
                :edit-url="classRoutes[policy.class].edit(policy.slug).url"
            />
        </div>

        <!-- Mobile: pagination footer for the card list -->
        <div
            class="mt-2.5 rounded-lg border border-border bg-surface shadow-card md:hidden"
        >
            <Pagination :meta="policies.meta" item-label="policies" />
        </div>

        <!-- Desktop (`md` and above): table layout -->
        <div
            class="hidden rounded-lg border border-border bg-surface shadow-card md:block"
        >
            <div class="min-h-[488px]">
                <table class="w-full table-fixed border-collapse">
                    <colgroup>
                        <col style="width: 28%" />
                        <col style="width: 14%" />
                        <col style="width: 18%" />
                        <col style="width: 16%" />
                        <col style="width: 11%" />
                        <col style="width: 9%" />
                        <col class="w-[60px]" />
                    </colgroup>
                    <thead>
                        <tr class="border-b border-border bg-sunken">
                            <th
                                class="rounded-tl-lg px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                            >
                                Policy
                            </th>
                            <th
                                class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                            >
                                Type · Class
                            </th>
                            <th
                                class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                            >
                                Client
                            </th>
                            <th
                                class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                            >
                                Effective → Expiry
                            </th>
                            <th
                                class="px-4 py-2.5 text-right font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                            >
                                Amount
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
                            v-for="(policy, index) in policies.data"
                            :key="policy.id"
                            class="cursor-pointer transition-colors hover:bg-sunken"
                            :class="
                                index !== policies.data.length - 1 &&
                                'border-b border-border-subtle'
                            "
                            @click="goToPolicy(policy)"
                        >
                            <td class="min-w-0 px-4 py-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div
                                        class="flex size-9 shrink-0 items-center justify-center rounded-lg border font-mono text-[10.5px] font-bold tracking-wider uppercase"
                                        :style="{
                                            backgroundColor: `${classColor(policy)}15`,
                                            borderColor: `${classColor(policy)}30`,
                                            color: classColor(policy),
                                        }"
                                    >
                                        {{ policy.class_label.slice(0, 3) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div
                                            class="truncate text-[13.5px] font-medium text-primary"
                                        >
                                            {{ policy.policy_number }}
                                        </div>
                                        <div
                                            class="mt-0.5 truncate font-mono text-[11.5px] text-tertiary"
                                        >
                                            {{ policy.subclass }} ·
                                            {{ policy.carrier.name }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :tone="
                                        policy.type === 'group'
                                            ? 'accent'
                                            : 'neutral'
                                    "
                                >
                                    {{ policy.type_label }}
                                </Badge>
                                <div
                                    class="mt-1 font-mono text-[11.5px] text-secondary"
                                >
                                    {{ policy.class_label }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex min-w-0 items-center gap-2">
                                    <Avatar
                                        :name="policy.client.full_name"
                                        :size="26"
                                    />
                                    <span
                                        class="truncate text-[13px] text-primary"
                                    >
                                        {{ policy.client.full_name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-[12.5px]">
                                <div class="text-secondary">
                                    {{ policy.effective_date_formatted }}
                                </div>
                                <div class="mt-0.5 text-tertiary">
                                    → {{ policy.expiry_date_formatted }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div
                                    class="font-mono text-[13px] font-medium text-primary"
                                >
                                    {{ policy.net_premium }}
                                </div>
                                <div
                                    v-if="Number(policy.discount_amount) > 0"
                                    class="mt-0.5 font-mono text-[11px] text-tertiary"
                                >
                                    −{{ policy.discount_amount }} disc
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :tone="
                                        policyStatusTone[policy.status] ??
                                        'neutral'
                                    "
                                    dot
                                >
                                    {{ policy.status_label }}
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
                                        :href="
                                            classRoutes[policy.class].edit(
                                                policy.slug,
                                            ).url
                                        "
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

            <Pagination :meta="policies.meta" item-label="policies" />
        </div>
    </div>
</template>
