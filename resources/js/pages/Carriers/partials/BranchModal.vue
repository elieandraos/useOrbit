<script setup lang="ts">
import type { FormComponentRef } from '@inertiajs/core';
import { Form } from '@inertiajs/vue3';
import { Mail, MapPin, Phone } from '@lucide/vue';
import { computed, ref, useTemplateRef, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import FormField from '@/components/ui/form-field/FormField.vue';
import Input from '@/components/ui/input/Input.vue';
import { Typeahead } from '@/components/ui/typeahead';
import type { TypeaheadOption } from '@/components/ui/typeahead';
import { useStateOptions } from '@/composables/useWorldLocations';
import {
    store as carriersBranchesStore,
    update as carriersBranchesUpdate,
} from '@/routes/carriers/branches';
import type { CarrierBranchResource, CarrierResource } from './carrier';

const props = defineProps<{
    carrier: CarrierResource;
    countries: { id: number; name: string }[];
    branch?: CarrierBranchResource | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const branchFormRef = useTemplateRef<FormComponentRef>('branchFormRef');

const isEditing = computed(() => !!props.branch);
const formDefinition = computed(() =>
    props.branch
        ? carriersBranchesUpdate.form(props.branch.id)
        : carriersBranchesStore.form(props.carrier.slug),
);

const street = ref('');
const buildingFloor = ref('');
const city = ref('');
const countryId = ref<number | null>(null);
const stateId = ref<number | null>(null);
const stateLabel = ref<string | undefined>(undefined);

const contactName = ref('');
const contactRole = ref('');
const contactEmail = ref('');
const contactPhone = ref('');

const countryOptions = computed<TypeaheadOption[]>(() =>
    props.countries.map((country) => ({
        value: country.id,
        label: country.name,
    })),
);
const { options: stateOptions, loading: stateLoading } =
    useStateOptions(countryId);

function resetFields() {
    street.value = '';
    buildingFloor.value = '';
    city.value = '';
    countryId.value = null;
    stateId.value = null;
    stateLabel.value = undefined;
    contactName.value = '';
    contactRole.value = '';
    contactEmail.value = '';
    contactPhone.value = '';
}

function seedFields(branch: CarrierBranchResource) {
    street.value = branch.street ?? '';
    buildingFloor.value = branch.building_floor ?? '';
    city.value = branch.city ?? '';
    countryId.value = branch.country_id;
    stateId.value = branch.state_id;
    stateLabel.value = branch.state_name ?? undefined;
    contactName.value = branch.contact_name ?? '';
    contactRole.value = branch.contact_role ?? '';
    contactEmail.value = branch.contact_email ?? '';
    contactPhone.value = branch.contact_phone ?? '';
}

watch(open, (isOpen) => {
    if (!isOpen) {
        branchFormRef.value?.clearErrors();

        return;
    }

    if (props.branch) {
        seedFields(props.branch);
    } else {
        resetFields();
    }
});

function handleSuccess() {
    open.value = false;
    resetFields();
}
</script>

<template>
    <Form
        ref="branchFormRef"
        v-bind="formDefinition"
        :options="{ preserveScroll: true }"
        v-slot="{ errors, processing }"
        @success="handleSuccess"
    >
        <Dialog
            v-model:open="open"
            :title="isEditing ? 'Edit branch' : 'Add branch'"
            :description="
                isEditing
                    ? 'Update this branch\'s address and contact details.'
                    : 'Add another branch location for this carrier.'
            "
        >
            <div class="flex flex-col gap-4">
                <p
                    class="text-[11px] font-semibold tracking-[0.04em] text-tertiary uppercase"
                >
                    Address
                </p>
                <FormField
                    label="Building / Floor"
                    for="branch_modal_building_floor"
                    optional
                    :error="errors.building_floor"
                >
                    <Input
                        id="branch_modal_building_floor"
                        v-model="buildingFloor"
                        name="building_floor"
                    />
                </FormField>
                <FormField
                    label="Street"
                    for="branch_modal_street"
                    optional
                    :error="errors.street"
                >
                    <Input
                        id="branch_modal_street"
                        v-model="street"
                        name="street"
                    >
                        <template #leading>
                            <MapPin />
                        </template>
                    </Input>
                </FormField>
                <FormField
                    label="City"
                    for="branch_modal_city"
                    required
                    :error="errors.city"
                >
                    <Input id="branch_modal_city" v-model="city" name="city" />
                </FormField>
                <FormField
                    label="State"
                    for="branch_modal_state_id"
                    optional
                    :error="errors.state_id"
                >
                    <Typeahead
                        id="branch_modal_state_id"
                        v-model="stateId"
                        name="state_id"
                        :options="stateOptions"
                        :loading="stateLoading"
                        :initial-label="stateLabel"
                        placeholder="Select"
                    />
                </FormField>
                <FormField
                    label="Country"
                    for="branch_modal_country_id"
                    optional
                    :error="errors.country_id"
                >
                    <Typeahead
                        id="branch_modal_country_id"
                        v-model="countryId"
                        name="country_id"
                        :options="countryOptions"
                        placeholder="Select"
                    />
                </FormField>

                <p
                    class="mt-1 border-t border-border-subtle pt-4 text-[11px] font-semibold tracking-[0.04em] text-tertiary uppercase"
                >
                    Contact person
                </p>
                <FormField
                    label="Contact name"
                    for="branch_modal_contact_name"
                    required
                    :error="errors.contact_name"
                >
                    <Input
                        id="branch_modal_contact_name"
                        v-model="contactName"
                        name="contact_name"
                    />
                </FormField>
                <FormField
                    label="Title / Role"
                    for="branch_modal_contact_role"
                    optional
                    :error="errors.contact_role"
                >
                    <Input
                        id="branch_modal_contact_role"
                        v-model="contactRole"
                        name="contact_role"
                    />
                </FormField>
                <FormField
                    label="Email"
                    for="branch_modal_contact_email"
                    optional
                    :error="errors.contact_email"
                >
                    <Input
                        id="branch_modal_contact_email"
                        v-model="contactEmail"
                        name="contact_email"
                        type="email"
                    >
                        <template #leading>
                            <Mail />
                        </template>
                    </Input>
                </FormField>
                <FormField
                    label="Phone"
                    for="branch_modal_contact_phone"
                    optional
                    :error="errors.contact_phone"
                >
                    <Input
                        id="branch_modal_contact_phone"
                        v-model="contactPhone"
                        name="contact_phone"
                        type="tel"
                    >
                        <template #leading>
                            <Phone />
                        </template>
                    </Input>
                </FormField>
            </div>

            <template #footer>
                <Button variant="secondary" @click="open = false">
                    Cancel
                </Button>
                <Button type="submit" :disabled="processing">
                    {{ isEditing ? 'Save changes' : 'Add branch' }}
                </Button>
            </template>
        </Dialog>
    </Form>
</template>
