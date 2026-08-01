<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { Mail, MapPin, Phone } from '@lucide/vue';
import { computed, ref } from 'vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import DateInput from '@/components/ui/date-input/DateInput.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import FormSection from '@/components/ui/form-section/FormSection.vue';
import Input from '@/components/ui/input/Input.vue';
import { Typeahead } from '@/components/ui/typeahead';
import type { TypeaheadOption } from '@/components/ui/typeahead';
import { useStateOptions } from '@/composables/useWorldLocations';
import { index as carriersIndex } from '@/routes/carriers';
import type { RouteFormDefinition } from '@/wayfinder';
import type { CarrierResource } from './carrier';

const props = defineProps<{
    countries: { id: number; name: string }[];
    carrier?: CarrierResource;
    route: RouteFormDefinition<'post'>;
    submitLabel: string;
}>();

const today = new Date().toISOString().slice(0, 10);

const name = ref(props.carrier?.name ?? '');
const phone = ref(props.carrier?.phone ?? '');
const website = ref(props.carrier?.website ?? '');
const onboardedDate = ref(props.carrier?.onboarded_date ?? today);

const defaultCountryId = computed(
    () => props.countries.find((country) => country.name === 'Lebanon')?.id ?? null,
);

const branchPhone = ref(props.carrier?.branch?.phone ?? '');
const branchBuildingFloor = ref(props.carrier?.branch?.building_floor ?? '');
const branchStreet = ref(props.carrier?.branch?.street ?? '');
const branchCity = ref(props.carrier?.branch?.city ?? '');
const branchCountryId = ref<number | null>(
    props.carrier?.branch?.country_id ?? defaultCountryId.value,
);
const branchStateId = ref<number | null>(props.carrier?.branch?.state_id ?? null);

const contactName = ref(props.carrier?.branch?.contact_name ?? '');
const contactRole = ref(props.carrier?.branch?.contact_role ?? '');
const contactEmail = ref(props.carrier?.branch?.contact_email ?? '');
const contactPhone = ref(props.carrier?.branch?.contact_phone ?? '');
const contactDepartment = ref(props.carrier?.branch?.contact_department ?? '');

const countryOptions = computed<TypeaheadOption[]>(() =>
    props.countries.map((country) => ({
        value: country.id,
        label: country.name,
    })),
);
const { options: stateOptions, loading: stateLoading } =
    useStateOptions(branchCountryId);
</script>

<template>
    <Form
        v-bind="route"
        v-slot="{ errors, processing }"
        class="mx-auto flex w-full max-w-[1100px] flex-col gap-4"
    >
        <FormSection
            title="Identity"
            subtitle="The carrier's name, contact, and onboarding date."
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <FormField
                    label="Carrier name"
                    for="name"
                    required
                    :error="errors.name"
                >
                    <Input id="name" v-model="name" name="name" />
                </FormField>
                <FormField
                    label="Phone"
                    for="phone"
                    optional
                    :error="errors.phone"
                >
                    <Input id="phone" v-model="phone" name="phone" type="tel">
                        <template #leading>
                            <Phone />
                        </template>
                    </Input>
                </FormField>
                <FormField
                    label="Website"
                    for="website"
                    optional
                    :error="errors.website"
                >
                    <Input id="website" v-model="website" name="website" />
                </FormField>
            </div>
            <FormField
                label="Onboarded date"
                required
                :error="errors.onboarded_date"
            >
                <div class="max-w-[360px]">
                    <DateInput
                        v-model="onboardedDate"
                        name="onboarded_date"
                    />
                </div>
            </FormField>
        </FormSection>

        <FormSection
            title="Headquarters"
            subtitle="Where is the carrier based? You can add more branches from the carrier page after creating it."
        >
            <template #badge>
                <Badge tone="accent" dot>Primary</Badge>
            </template>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Phone"
                    for="branch_phone"
                    optional
                    :error="errors['branch.phone']"
                >
                    <Input
                        id="branch_phone"
                        v-model="branchPhone"
                        name="branch[phone]"
                        type="tel"
                    >
                        <template #leading>
                            <Phone />
                        </template>
                    </Input>
                </FormField>
                <FormField
                    label="Building / Floor"
                    for="branch_building_floor"
                    optional
                    :error="errors['branch.building_floor']"
                >
                    <Input
                        id="branch_building_floor"
                        v-model="branchBuildingFloor"
                        name="branch[building_floor]"
                    />
                </FormField>
                <FormField
                    label="Street"
                    for="branch_street"
                    optional
                    :error="errors['branch.street']"
                >
                    <Input
                        id="branch_street"
                        v-model="branchStreet"
                        name="branch[street]"
                    >
                        <template #leading>
                            <MapPin />
                        </template>
                    </Input>
                </FormField>
                <FormField
                    label="City"
                    for="branch_city"
                    required
                    :error="errors['branch.city']"
                >
                    <Input
                        id="branch_city"
                        v-model="branchCity"
                        name="branch[city]"
                    />
                </FormField>
                <FormField
                    label="Governorate"
                    for="branch_state_id"
                    optional
                    :error="errors['branch.state_id']"
                >
                    <Typeahead
                        id="branch_state_id"
                        v-model="branchStateId"
                        name="branch[state_id]"
                        :options="stateOptions"
                        :loading="stateLoading"
                        :initial-label="carrier?.branch?.state_name"
                        placeholder="Select"
                    />
                </FormField>
                <FormField
                    label="Country"
                    for="branch_country_id"
                    optional
                    :error="errors['branch.country_id']"
                >
                    <Typeahead
                        id="branch_country_id"
                        v-model="branchCountryId"
                        name="branch[country_id]"
                        :options="countryOptions"
                        placeholder="Select"
                    />
                </FormField>
            </div>
        </FormSection>

        <FormSection
            title="Primary contact"
            subtitle="The person you'll work with most. They'll receive policy updates by default. More contacts can be added later."
        >
            <template #badge>
                <Badge tone="accent" dot>Primary</Badge>
            </template>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Full name"
                    for="contact_name"
                    required
                    :error="errors['contact.name']"
                >
                    <Input
                        id="contact_name"
                        v-model="contactName"
                        name="contact[name]"
                    />
                </FormField>
                <FormField
                    label="Title / Role"
                    for="contact_role"
                    optional
                    :error="errors['contact.role']"
                >
                    <Input
                        id="contact_role"
                        v-model="contactRole"
                        name="contact[role]"
                    />
                </FormField>
                <FormField
                    label="Email"
                    for="contact_email"
                    optional
                    :error="errors['contact.email']"
                >
                    <Input
                        id="contact_email"
                        v-model="contactEmail"
                        name="contact[email]"
                        type="email"
                    >
                        <template #leading>
                            <Mail />
                        </template>
                    </Input>
                </FormField>
                <FormField
                    label="Phone"
                    for="contact_phone"
                    optional
                    :error="errors['contact.phone']"
                >
                    <Input
                        id="contact_phone"
                        v-model="contactPhone"
                        name="contact[phone]"
                        type="tel"
                    >
                        <template #leading>
                            <Phone />
                        </template>
                    </Input>
                </FormField>
                <FormField
                    label="Department"
                    for="contact_department"
                    optional
                    :error="errors['contact.department']"
                >
                    <Input
                        id="contact_department"
                        v-model="contactDepartment"
                        name="contact[department]"
                    />
                </FormField>
            </div>
        </FormSection>

        <div class="flex justify-end gap-3">
            <Link :href="carriersIndex().url">
                <Button type="button" variant="ghost">Cancel</Button>
            </Link>
            <Button type="submit" :disabled="processing">{{
                submitLabel
            }}</Button>
        </div>
    </Form>
</template>
