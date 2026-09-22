<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { SearchIcon, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import Badge from '@/components/ui/badge/Badge.vue';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import Input from '@/components/ui/input/Input.vue';
import { cn } from '@/lib/utils';
import type {
    PolicyInsured,
    PolicyMedicalResource,
} from '../PolicyMedical/partials/policy';
import PolicyMedicalDetailShell from '../PolicyMedical/partials/PolicyMedicalDetailShell.vue';

const props = defineProps<{
    policy: PolicyMedicalResource;
    members: PolicyInsured[];
}>();

const search = ref('');
const activeRelationship = ref<string | null>(null);

const memberStatusTone: Record<string, 'success' | 'warning' | 'neutral'> = {
    Active: 'success',
    Pending: 'warning',
};

const relationships = computed(() => {
    const counts = new Map<string, number>();

    for (const member of props.members) {
        counts.set(
            member.relationship,
            (counts.get(member.relationship) ?? 0) + 1,
        );
    }

    return [...counts.entries()].map(([relationship, count]) => ({
        relationship,
        count,
    }));
});

const filteredMembers = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.members.filter((member) => {
        if (
            activeRelationship.value !== null &&
            member.relationship !== activeRelationship.value
        ) {
            return false;
        }

        if (query === '') {
            return true;
        }

        return (
            member.full_name.toLowerCase().includes(query) ||
            member.member_code.toLowerCase().includes(query)
        );
    });
});

const isFiltered = computed(
    () => search.value.trim() !== '' || activeRelationship.value !== null,
);
</script>

<template>
    <PolicyMedicalDetailShell :policy="policy">
        <Head :title="`${policy.policy_number} · Members`" />

        <Card class="mt-6 min-h-[488px]">
            <CardHeader bordered>
                <CardTitle>Members</CardTitle>
                <Badge v-if="members.length > 0" tone="accent">{{
                    members.length
                }}</Badge>
                <CardAction>
                    <Input
                        v-model="search"
                        size="sm"
                        placeholder="Search members…"
                        class="w-60"
                    >
                        <template #leading><SearchIcon /></template>
                    </Input>
                </CardAction>
            </CardHeader>

            <div
                v-if="relationships.length > 0"
                class="flex flex-wrap items-center gap-2 border-b border-border-subtle bg-sunken px-6 py-3"
            >
                <button
                    type="button"
                    :class="
                        cn(
                            'inline-flex h-[30px] cursor-pointer items-center gap-1.5 rounded-full border px-3 text-[12.5px] font-medium',
                            activeRelationship === null
                                ? 'border-accent bg-accent-bg text-accent'
                                : 'border-border bg-surface text-secondary hover:text-primary',
                        )
                    "
                    @click="activeRelationship = null"
                >
                    All
                    <span
                        class="font-mono text-[11px]"
                        :class="
                            activeRelationship === null
                                ? 'text-accent'
                                : 'text-tertiary'
                        "
                        >{{ members.length }}</span
                    >
                </button>

                <button
                    v-for="entry in relationships"
                    :key="entry.relationship"
                    type="button"
                    :class="
                        cn(
                            'inline-flex h-[30px] cursor-pointer items-center gap-1.5 rounded-full border px-3 text-[12.5px] font-medium',
                            activeRelationship === entry.relationship
                                ? 'border-accent bg-accent-bg text-accent'
                                : 'border-border bg-surface text-secondary hover:text-primary',
                        )
                    "
                    @click="activeRelationship = entry.relationship"
                >
                    {{ entry.relationship }}
                    <span
                        class="font-mono text-[11px]"
                        :class="
                            activeRelationship === entry.relationship
                                ? 'text-accent'
                                : 'text-tertiary'
                        "
                        >{{ entry.count }}</span
                    >
                </button>
            </div>

            <CardContent class="p-0">
                <div v-if="filteredMembers.length > 0" class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-border bg-sunken">
                                <th
                                    class="px-6 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                                >
                                    Member
                                </th>
                                <th
                                    class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                                >
                                    Member code
                                </th>
                                <th
                                    class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                                >
                                    Relationship
                                </th>
                                <th
                                    class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                                >
                                    Date of birth
                                </th>
                                <th
                                    class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                                >
                                    Age
                                </th>
                                <th
                                    class="px-4 py-2.5 text-left font-mono text-[11px] font-normal tracking-wider text-tertiary uppercase"
                                >
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="member in filteredMembers"
                                :key="member.id"
                                class="border-b border-border-subtle last:border-b-0"
                            >
                                <td
                                    class="px-6 py-3 text-[13.5px] font-medium text-primary"
                                >
                                    {{ member.full_name }}
                                </td>
                                <td
                                    class="px-4 py-3 font-mono text-[12.5px] text-secondary"
                                >
                                    {{ member.member_code }}
                                </td>
                                <td
                                    class="px-4 py-3 text-[13px] text-secondary"
                                >
                                    {{ member.relationship }}
                                </td>
                                <td
                                    class="px-4 py-3 font-mono text-[12.5px] text-secondary"
                                >
                                    {{ member.date_of_birth_formatted }}
                                </td>
                                <td
                                    class="px-4 py-3 text-[13px] text-secondary"
                                >
                                    {{ member.age }}
                                </td>
                                <td class="px-4 py-3">
                                    <Badge
                                        :tone="
                                            memberStatusTone[member.status] ??
                                            'neutral'
                                        "
                                        dot
                                    >
                                        {{ member.status }}
                                    </Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    v-else
                    class="flex flex-col items-center gap-2 py-10 text-center"
                >
                    <Users class="size-6 text-tertiary" />
                    <p class="text-sm text-secondary">
                        {{
                            isFiltered
                                ? 'No members match your search.'
                                : 'No members yet.'
                        }}
                    </p>
                </div>
            </CardContent>
        </Card>
    </PolicyMedicalDetailShell>
</template>
