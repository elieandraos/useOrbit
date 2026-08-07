<script setup lang="ts">
import type { FormComponentRef } from '@inertiajs/core';
import { Form } from '@inertiajs/vue3';
import { Check, Mail } from '@lucide/vue';
import { ref, useTemplateRef, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import FormField from '@/components/ui/form-field/FormField.vue';
import Input from '@/components/ui/input/Input.vue';
import { RadioCard } from '@/components/ui/radio-card';
import { store as organizationMembersStore } from '@/routes/organization-members';
import type { InvitableRoleOption } from './organizationMember';
import { ROLE_DESCRIPTIONS } from './organizationMember';

defineProps<{
    roleOptions: InvitableRoleOption[];
}>();

const open = defineModel<boolean>('open', { default: false });

const inviteFormRef = useTemplateRef<FormComponentRef>('inviteFormRef');

const name = ref('');
const email = ref('');
const role = ref('member');
const sent = ref(false);

function resetFields() {
    name.value = '';
    email.value = '';
    role.value = 'member';
    sent.value = false;
}

watch(open, (isOpen) => {
    if (!isOpen) {
        inviteFormRef.value?.clearErrors();
        resetFields();
    }
});

function handleSuccess() {
    sent.value = true;
}

function close() {
    open.value = false;
}
</script>

<template>
    <Form
        ref="inviteFormRef"
        v-bind="organizationMembersStore.form()"
        :options="{ preserveScroll: true }"
        v-slot="{ errors, processing }"
        @success="handleSuccess"
    >
        <Dialog
            v-model:open="open"
            :title="sent ? undefined : 'Invite a member'"
            :description="
                sent
                    ? undefined
                    : `They'll receive an email invitation to join this organization.`
            "
        >
            <div v-if="!sent" class="flex flex-col gap-4.5">
                <FormField
                    label="Full name"
                    for="invite_member_name"
                    required
                    :error="errors.name"
                >
                    <Input
                        id="invite_member_name"
                        v-model="name"
                        name="name"
                        placeholder="Jordan Casey"
                    />
                </FormField>

                <FormField
                    label="Email address"
                    for="invite_member_email"
                    required
                    :error="errors.email"
                >
                    <Input
                        id="invite_member_email"
                        v-model="email"
                        name="email"
                        type="email"
                        placeholder="colleague@example.com"
                    >
                        <template #leading>
                            <Mail />
                        </template>
                    </Input>
                </FormField>

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
            </div>

            <div
                v-else
                class="flex flex-col items-center px-1 pt-5 pb-2 text-center"
            >
                <span
                    class="mb-4 inline-flex size-10 items-center justify-center rounded-full bg-accent-bg text-accent"
                >
                    <Check class="size-5.5" stroke-width="2.4" />
                </span>
                <p class="text-[17px] font-semibold text-primary">
                    Invitation sent
                </p>
                <p
                    class="mt-2 max-w-[330px] text-[13.5px] leading-[1.55] text-pretty text-secondary"
                >
                    An invitation has been sent to
                    <span class="font-medium text-primary">{{ email }}</span
                    >. They'll appear in your member list once they accept.
                </p>
            </div>

            <template #footer>
                <template v-if="!sent">
                    <Button variant="secondary" @click="close">Cancel</Button>
                    <Button type="submit" :disabled="processing">
                        Send Invitation
                    </Button>
                </template>
                <div v-else class="flex w-full justify-center">
                    <Button @click="close">Done</Button>
                </div>
            </template>
        </Dialog>
    </Form>
</template>
