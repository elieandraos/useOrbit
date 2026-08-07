<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import FormField from '@/components/ui/form-field/FormField.vue';
import { RadioCard } from '@/components/ui/radio-card';
import { changeRole as organizationMembersChangeRole } from '@/routes/organization-members';
import type {
    InvitableRoleOption,
    OrganizationMemberResource,
} from './organizationMember';
import { ROLE_DESCRIPTIONS } from './organizationMember';

defineProps<{
    roleOptions: InvitableRoleOption[];
}>();

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

const role = ref('member');

watch(
    member,
    (current) => {
        if (current) {
            role.value = current.role;
        }
    },
    { immediate: true },
);
</script>

<template>
    <Form
        v-if="member"
        v-bind="organizationMembersChangeRole.form(member.id)"
        :options="{ preserveScroll: true }"
        v-slot="{ errors, processing }"
        @success="member = null"
    >
        <Dialog
            v-model:open="isOpen"
            :title="`Change role for ${member?.name ?? member?.email}?`"
            description="They'll immediately gain or lose admin-level access, including member management and billing."
        >
            <FormField label="Role" required :error="errors.role">
                <RadioCard
                    v-model="role"
                    name="role"
                    :options="
                        roleOptions.map((option) => ({
                            ...option,
                            desc: ROLE_DESCRIPTIONS[option.value],
                        }))
                    "
                />
            </FormField>

            <template #footer>
                <Button variant="secondary" @click="member = null">
                    Cancel
                </Button>
                <Button type="submit" :disabled="processing">
                    Change Role
                </Button>
            </template>
        </Dialog>
    </Form>
</template>
