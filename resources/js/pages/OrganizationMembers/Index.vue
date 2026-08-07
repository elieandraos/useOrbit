<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import Button from '@/components/ui/button/Button.vue';
import type { OrganizationMemberResource } from './partials/organizationMember';
import OrganizationMembersTable from './partials/OrganizationMembersTable.vue';

defineProps<{
    members: OrganizationMemberResource[];
}>();
</script>

<template>
    <Head title="Members" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="Members"
            subtitle="Invite teammates and manage their access to this organization"
        >
            <template #actions>
                <Button variant="primary" size="md">
                    <template #leading><Plus /></template>
                    Invite Member
                </Button>
            </template>
        </PageHeader>

        <OrganizationMembersTable class="mt-5" :members="members" />
    </div>
</template>
