<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { revokeInvitation as organizationMembersRevokeInvitation } from '@/routes/organization-members';
import type { OrganizationMemberResource } from './organizationMember';

const member = defineModel<OrganizationMemberResource | null>({
    default: null,
});

const isOpen = computed({
    get: () => member.value !== null,
    set: (value) => {
        if (!value) {
            member.value = null;
        }
    },
});
</script>

<template>
    <Form
        v-if="member"
        v-bind="organizationMembersRevokeInvitation.form(member.id)"
        :options="{ preserveScroll: true }"
        v-slot="{ processing }"
        @success="member = null"
    >
        <Dialog
            v-model:open="isOpen"
            :title="`Revoke invitation to ${member?.email}?`"
            description="They won't be able to accept this invitation anymore. You can invite them again later."
        >
            <template #footer>
                <Button variant="secondary" @click="member = null">
                    Cancel
                </Button>
                <Button
                    type="submit"
                    variant="destructive"
                    :disabled="processing"
                >
                    Revoke Invitation
                </Button>
            </template>
        </Dialog>
    </Form>
</template>
