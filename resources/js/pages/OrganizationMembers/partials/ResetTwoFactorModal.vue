<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { resetTwoFactor as organizationMembersResetTwoFactor } from '@/routes/organization-members';
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
        v-bind="organizationMembersResetTwoFactor.form(member.id)"
        :options="{ preserveScroll: true }"
        v-slot="{ processing }"
        @success="member = null"
    >
        <Dialog
            v-model:open="isOpen"
            :title="`Reset two-factor authentication for ${member?.name ?? member?.email}?`"
            description="They'll be signed out of their current two-factor enrollment and will need to set it up again. This can't be undone."
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
                    Reset 2FA
                </Button>
            </template>
        </Dialog>
    </Form>
</template>
