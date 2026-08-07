<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import Button from '@/components/ui/button/Button.vue';
import InviteMemberModal from './partials/InviteMemberModal.vue';
import type {
    InvitableRoleOption,
    OrganizationMemberResource,
} from './partials/organizationMember';
import OrganizationMembersTable from './partials/OrganizationMembersTable.vue';

defineProps<{
    members: OrganizationMemberResource[];
    roleOptions: InvitableRoleOption[];
}>();

const inviteModalOpen = ref(false);
</script>

<template>
    <Head title="Members" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="Members"
            subtitle="Invite teammates and manage their access to this organization"
        >
            <template #actions>
                <Button
                    variant="primary"
                    size="md"
                    @click="inviteModalOpen = true"
                >
                    <template #leading><Plus /></template>
                    Invite Member
                </Button>
            </template>
        </PageHeader>

        <OrganizationMembersTable class="mt-5" :members="members" />

        <InviteMemberModal
            v-model:open="inviteModalOpen"
            :role-options="roleOptions"
        />
    </div>
</template>
