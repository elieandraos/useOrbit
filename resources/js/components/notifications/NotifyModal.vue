<script setup lang="ts">
import type { FormComponentRef } from '@inertiajs/core';
import { Form, useHttp } from '@inertiajs/vue3';
import { ref, useTemplateRef, watch } from 'vue';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog } from '@/components/ui/dialog';
import FormField from '@/components/ui/form-field/FormField.vue';
import { RadioCard } from '@/components/ui/radio-card';
import { NOTIFY_REASON_OPTIONS } from '@/lib/notifyReasons';
import { recipients as notifyRecipients } from '@/routes/notify';
import type { RouteFormDefinition } from '@/wayfinder';

type Recipient = { id: number; name: string; email: string };

const props = defineProps<{
    subjectLabel: string;
    form: RouteFormDefinition<'post'>;
}>();

const open = defineModel<boolean>('open', { default: false });

const notifyFormRef = useTemplateRef<FormComponentRef>('notifyFormRef');

const loading = ref(false);
const recipientList = ref<Recipient[]>([]);
const selectedIds = ref<number[]>([]);
const reason = ref(NOTIFY_REASON_OPTIONS[0].value);

function isSelected(id: number): boolean {
    return selectedIds.value.includes(id);
}

function toggleRecipient(id: number, checked: boolean): void {
    if (checked) {
        selectedIds.value.push(id);
    } else {
        selectedIds.value = selectedIds.value.filter(
            (existing) => existing !== id,
        );
    }
}

function resetFields(): void {
    recipientList.value = [];
    selectedIds.value = [];
    reason.value = NOTIFY_REASON_OPTIONS[0].value;
}

function loadRecipients(): void {
    loading.value = true;

    useHttp({}).get(notifyRecipients().url, {
        onSuccess: (response) => {
            recipientList.value = (response as { data: Recipient[] }).data;
            loading.value = false;
        },
        onError: () => {
            loading.value = false;
        },
    });
}

watch(open, (isOpen) => {
    if (isOpen) {
        loadRecipients();
    } else {
        notifyFormRef.value?.clearErrors();
        resetFields();
    }
});

function close(): void {
    open.value = false;
}
</script>

<template>
    <Form
        ref="notifyFormRef"
        v-bind="props.form"
        :options="{ preserveScroll: true }"
        v-slot="{ errors, processing }"
        @success="close"
    >
        <Dialog v-model:open="open" title="Notify about">
            <div class="flex flex-col gap-4.5">
                <p class="text-[15px] font-semibold text-primary">
                    {{ subjectLabel }}
                </p>

                <FormField
                    label="Recipients"
                    required
                    :error="errors.recipient_ids"
                >
                    <div
                        v-if="loading"
                        class="rounded-md border border-border px-3.5 py-3 text-sm text-secondary"
                    >
                        Loading recipients…
                    </div>
                    <div
                        v-else-if="recipientList.length === 0"
                        class="rounded-md border border-border px-3.5 py-3 text-sm text-secondary"
                    >
                        No one else to notify.
                    </div>
                    <div
                        v-else
                        class="overflow-hidden rounded-md border border-border"
                    >
                        <label
                            v-for="(recipient, index) in recipientList"
                            :key="recipient.id"
                            class="flex cursor-pointer items-center gap-3 px-3.5 py-2.5"
                            :class="
                                index !== recipientList.length - 1 &&
                                'border-b border-border-subtle'
                            "
                        >
                            <Checkbox
                                name="recipient_ids[]"
                                :value="recipient.id"
                                :model-value="isSelected(recipient.id)"
                                @update:model-value="
                                    (checked) =>
                                        toggleRecipient(recipient.id, checked)
                                "
                            />
                            <Avatar :name="recipient.name" :size="28" />
                            <span
                                class="min-w-0 flex-1 truncate text-[13.5px] font-medium text-primary"
                                >{{ recipient.name }}</span
                            >
                        </label>
                    </div>
                </FormField>

                <FormField
                    label="Why are you notifying them?"
                    required
                    :error="errors.reason"
                >
                    <RadioCard
                        v-model="reason"
                        name="reason"
                        :options="NOTIFY_REASON_OPTIONS"
                    />
                </FormField>
            </div>

            <template #footer>
                <Button variant="secondary" @click="close">Cancel</Button>
                <Button
                    type="submit"
                    :disabled="processing || selectedIds.length === 0"
                >
                    Notify
                </Button>
            </template>
        </Dialog>
    </Form>
</template>
