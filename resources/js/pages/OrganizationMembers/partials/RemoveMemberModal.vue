<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import FormField from '@/components/ui/form-field/FormField.vue';
import Select from '@/components/ui/select/Select.vue';
import { destroy as organizationMembersDestroy } from '@/routes/organization-members';
import type { OrganizationMemberResource } from './organizationMember';

const props = defineProps<{
    members: OrganizationMemberResource[];
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

const reassignTo = ref('');

const reassignOptions = computed(() =>
    props.members.filter(
        (candidate) =>
            candidate.status === 'active' && candidate.id !== member.value?.id,
    ),
);

watch(member, () => {
    reassignTo.value = '';
});
</script>

<template>
    <Form
        v-if="member"
        v-bind="organizationMembersDestroy.form(member.id)"
        :options="{ preserveScroll: true }"
        v-slot="{ errors, processing }"
        @success="member = null"
    >
        <Dialog
            v-model:open="isOpen"
            :title="`Remove ${member?.name ?? member?.email}?`"
            description="They'll lose access to this organization immediately. Choose who should take over their clients, policies, and documents."
        >
            <FormField
                label="Reassign their work to"
                for="remove_member_reassign_to"
                required
                :error="errors.reassign_to"
            >
                <Select
                    id="remove_member_reassign_to"
                    v-model="reassignTo"
                    name="reassign_to"
                    placeholder="Select a member"
                >
                    <option
                        v-for="candidate in reassignOptions"
                        :key="candidate.id"
                        :value="String(candidate.id)"
                    >
                        {{ candidate.name ?? candidate.email
                        }}<template v-if="candidate.is_you"> (You)</template
                        ><template v-if="candidate.role === 'owner'">
                            — Owner</template
                        >
                    </option>
                </Select>
            </FormField>

            <template #footer>
                <Button variant="secondary" @click="member = null">
                    Cancel
                </Button>
                <Button
                    type="submit"
                    variant="destructive"
                    :disabled="processing"
                >
                    Remove Member
                </Button>
            </template>
        </Dialog>
    </Form>
</template>
