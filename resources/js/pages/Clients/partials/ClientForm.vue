<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { Mail, MapPin, Phone } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import DateInput from '@/components/ui/date-input/DateInput.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import FormSection from '@/components/ui/form-section/FormSection.vue';
import Input from '@/components/ui/input/Input.vue';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';
import Select from '@/components/ui/select/Select.vue';
import { Typeahead } from '@/components/ui/typeahead';
import type { TypeaheadOption } from '@/components/ui/typeahead';
import { useStateOptions } from '@/composables/useWorldLocations';
import { index as clientsIndex } from '@/routes/clients';
import type { RouteFormDefinition } from '@/wayfinder';

interface ClientFormValues {
    client_type: string;
    company_name: string | null;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    mothers_name: string | null;
    date_of_birth: string | null;
    gender: string | null;
    phone: string;
    email: string | null;
    street: string | null;
    building_floor: string | null;
    state_id: number | null;
    city: string | null;
    country_id: number | null;
    country_name: string | null;
    state_name: string | null;
    emergency_contact_name: string | null;
    emergency_contact_relationship: string | null;
    emergency_contact_phone: string | null;
    enrollment_date: string;
    lead_source: string;
}

const props = defineProps<{
    countries: { id: number; name: string }[];
    genders: { label: string; value: string }[];
    leadSources: { label: string; value: string }[];
    emergencyContactRelationships: { label: string; value: string }[];
    clientTypes?: { label: string; value: string }[];
    client?: ClientFormValues;
    defaultCountryId?: number | null;
    route: RouteFormDefinition<'post'>;
    submitLabel: string;
}>();

const clientType = ref(props.client?.client_type ?? 'individual');
const companyName = ref(props.client?.company_name ?? '');
const firstName = ref(props.client?.first_name ?? '');
const middleName = ref(props.client?.middle_name ?? '');
const lastName = ref(props.client?.last_name ?? '');
const mothersName = ref(props.client?.mothers_name ?? '');
const dateOfBirth = ref(props.client?.date_of_birth ?? '');
const gender = ref(props.client?.gender ?? 'female');
const phone = ref(props.client?.phone ?? '');
const email = ref(props.client?.email ?? '');
const street = ref(props.client?.street ?? '');
const buildingFloor = ref(props.client?.building_floor ?? '');
const countryId = ref<number | null>(
    props.client?.country_id ?? props.defaultCountryId ?? null,
);
const stateId = ref<number | null>(props.client?.state_id ?? null);
const city = ref(props.client?.city ?? '');
const emergencyContactName = ref(props.client?.emergency_contact_name ?? '');
const emergencyContactRelationship = ref(
    props.client?.emergency_contact_relationship ?? '',
);
const emergencyContactPhone = ref(props.client?.emergency_contact_phone ?? '');
const enrollmentDate = ref(props.client?.enrollment_date ?? '');
const leadSource = ref(props.client?.lead_source ?? '');

const countryOptions = computed<TypeaheadOption[]>(() =>
    props.countries.map((country) => ({
        value: country.id,
        label: country.name,
    })),
);
const { options: stateOptions, loading: stateLoading } =
    useStateOptions(countryId);

watch(countryId, () => {
    stateId.value = null;
});
</script>

<template>
    <Form
        v-bind="route"
        v-slot="{ errors, processing }"
        class="mx-auto flex w-full max-w-[1100px] flex-col gap-4"
    >
        <FormSection
            v-if="!client"
            title="Client type"
            subtitle="Is this an individual or a company?"
        >
            <RadioChips
                v-model="clientType"
                name="client_type"
                :options="clientTypes ?? []"
            />
        </FormSection>

        <template v-if="clientType === 'individual'">
            <FormSection
                title="Personal information"
                subtitle="Legal name as it appears on policy documents."
            >
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <FormField
                        label="First name"
                        for="first_name"
                        required
                        :error="errors.first_name"
                    >
                        <Input
                            id="first_name"
                            v-model="firstName"
                            name="first_name"
                        />
                    </FormField>
                    <FormField
                        label="Middle name"
                        for="middle_name"
                        optional
                        :error="errors.middle_name"
                    >
                        <Input
                            id="middle_name"
                            v-model="middleName"
                            name="middle_name"
                        />
                    </FormField>
                    <FormField
                        label="Last name"
                        for="last_name"
                        required
                        :error="errors.last_name"
                    >
                        <Input
                            id="last_name"
                            v-model="lastName"
                            name="last_name"
                        />
                    </FormField>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField
                        label="Mother's name"
                        for="mothers_name"
                        optional
                        helper="Used by some carriers as a verification field."
                        :error="errors.mothers_name"
                    >
                        <Input
                            id="mothers_name"
                            v-model="mothersName"
                            name="mothers_name"
                        />
                    </FormField>
                    <FormField
                        label="Date of birth"
                        required
                        :error="errors.date_of_birth"
                    >
                        <DateInput v-model="dateOfBirth" name="date_of_birth" />
                    </FormField>
                </div>
                <FormField label="Gender" required :error="errors.gender">
                    <RadioChips
                        v-model="gender"
                        name="gender"
                        :options="genders"
                    />
                </FormField>
            </FormSection>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <FormSection
                    title="Contact"
                    subtitle="At least one of phone or email is required."
                >
                    <FormField
                        label="Phone number"
                        for="phone"
                        required
                        :error="errors.phone"
                    >
                        <Input
                            id="phone"
                            v-model="phone"
                            name="phone"
                            type="tel"
                        >
                            <template #leading>
                                <Phone />
                            </template>
                        </Input>
                    </FormField>
                    <FormField
                        label="Email"
                        for="email"
                        optional
                        :error="errors.email"
                    >
                        <Input
                            id="email"
                            v-model="email"
                            name="email"
                            type="email"
                        >
                            <template #leading>
                                <Mail />
                            </template>
                        </Input>
                    </FormField>
                    <FormField
                        label="Street"
                        for="street"
                        optional
                        :error="errors.street"
                    >
                        <Input id="street" v-model="street" name="street">
                            <template #leading>
                                <MapPin />
                            </template>
                        </Input>
                    </FormField>
                    <FormField
                        label="Building / Floor"
                        for="building_floor"
                        optional
                        :error="errors.building_floor"
                    >
                        <Input
                            id="building_floor"
                            v-model="buildingFloor"
                            name="building_floor"
                        />
                    </FormField>
                    <FormField
                        label="Country"
                        for="country_id"
                        optional
                        :error="errors.country_id"
                    >
                        <Typeahead
                            id="country_id"
                            v-model="countryId"
                            name="country_id"
                            :options="countryOptions"
                            placeholder="Select"
                        />
                    </FormField>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <FormField
                            label="Governorate"
                            for="state_id"
                            optional
                            :error="errors.state_id"
                        >
                            <Typeahead
                                id="state_id"
                                v-model="stateId"
                                name="state_id"
                                :options="stateOptions"
                                :loading="stateLoading"
                                :initial-label="client?.state_name"
                                placeholder="Select"
                            />
                        </FormField>
                        <FormField
                            label="City"
                            for="city"
                            optional
                            :error="errors.city"
                        >
                            <Input id="city" v-model="city" name="city" />
                        </FormField>
                    </div>
                </FormSection>

                <FormSection
                    title="Emergency contact"
                    subtitle="Recommended for senior and Medicare clients."
                >
                    <FormField
                        label="Full name"
                        for="emergency_contact_name"
                        optional
                        :error="errors.emergency_contact_name"
                    >
                        <Input
                            id="emergency_contact_name"
                            v-model="emergencyContactName"
                            name="emergency_contact_name"
                        />
                    </FormField>
                    <FormField
                        label="Relationship"
                        for="emergency_contact_relationship"
                        optional
                        :error="errors.emergency_contact_relationship"
                    >
                        <Select
                            id="emergency_contact_relationship"
                            v-model="emergencyContactRelationship"
                            name="emergency_contact_relationship"
                            placeholder="Select"
                        >
                            <option
                                v-for="rel in emergencyContactRelationships"
                                :key="rel.value"
                                :value="rel.value"
                            >
                                {{ rel.label }}
                            </option>
                        </Select>
                    </FormField>
                    <FormField
                        label="Phone number"
                        for="emergency_contact_phone"
                        optional
                        :error="errors.emergency_contact_phone"
                    >
                        <Input
                            id="emergency_contact_phone"
                            v-model="emergencyContactPhone"
                            name="emergency_contact_phone"
                            type="tel"
                        />
                    </FormField>
                </FormSection>
            </div>
        </template>

        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <FormSection
                title="Company information"
                subtitle="The business name and its address."
            >
                <FormField
                    label="Company name"
                    for="company_name"
                    required
                    :error="errors.company_name"
                >
                    <Input
                        id="company_name"
                        v-model="companyName"
                        name="company_name"
                    />
                </FormField>
                <FormField
                    label="Street"
                    for="street"
                    optional
                    :error="errors.street"
                >
                    <Input id="street" v-model="street" name="street">
                        <template #leading>
                            <MapPin />
                        </template>
                    </Input>
                </FormField>
                <FormField
                    label="Building / Floor"
                    for="building_floor"
                    optional
                    :error="errors.building_floor"
                >
                    <Input
                        id="building_floor"
                        v-model="buildingFloor"
                        name="building_floor"
                    />
                </FormField>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField
                        label="Governorate"
                        for="state_id"
                        optional
                        :error="errors.state_id"
                    >
                        <Typeahead
                            id="state_id"
                            v-model="stateId"
                            name="state_id"
                            :options="stateOptions"
                            :loading="stateLoading"
                            :initial-label="client?.state_name"
                            placeholder="Select"
                        />
                    </FormField>
                    <FormField
                        label="City"
                        for="city"
                        optional
                        :error="errors.city"
                    >
                        <Input id="city" v-model="city" name="city" />
                    </FormField>
                </div>
                <FormField
                    label="Country"
                    for="country_id"
                    optional
                    :error="errors.country_id"
                >
                    <Typeahead
                        id="country_id"
                        v-model="countryId"
                        name="country_id"
                        :options="countryOptions"
                        placeholder="Select"
                    />
                </FormField>
            </FormSection>

            <FormSection
                title="Contact person"
                subtitle="The person you'll work with day to day."
            >
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField
                        label="First name"
                        for="first_name"
                        required
                        :error="errors.first_name"
                    >
                        <Input
                            id="first_name"
                            v-model="firstName"
                            name="first_name"
                        />
                    </FormField>
                    <FormField
                        label="Last name"
                        for="last_name"
                        required
                        :error="errors.last_name"
                    >
                        <Input
                            id="last_name"
                            v-model="lastName"
                            name="last_name"
                        />
                    </FormField>
                </div>
                <FormField
                    label="Email"
                    for="email"
                    required
                    :error="errors.email"
                >
                    <Input id="email" v-model="email" name="email" type="email">
                        <template #leading>
                            <Mail />
                        </template>
                    </Input>
                </FormField>
                <FormField
                    label="Phone number"
                    for="phone"
                    required
                    :error="errors.phone"
                >
                    <Input id="phone" v-model="phone" name="phone" type="tel">
                        <template #leading>
                            <Phone />
                        </template>
                    </Input>
                </FormField>
            </FormSection>
        </div>

        <FormSection
            title="Enrollment"
            subtitle="When this client joined your book and how they found you."
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Enrollment date"
                    required
                    :error="errors.enrollment_date"
                >
                    <DateInput
                        v-model="enrollmentDate"
                        name="enrollment_date"
                    />
                </FormField>
                <FormField
                    label="Lead source"
                    for="lead_source"
                    required
                    :error="errors.lead_source"
                >
                    <Select
                        id="lead_source"
                        v-model="leadSource"
                        name="lead_source"
                        placeholder="Select"
                    >
                        <option
                            v-for="source in leadSources"
                            :key="source.value"
                            :value="source.value"
                        >
                            {{ source.label }}
                        </option>
                    </Select>
                </FormField>
            </div>
        </FormSection>

        <div class="flex justify-end gap-3">
            <Link :href="clientsIndex().url">
                <Button type="button" variant="ghost">Cancel</Button>
            </Link>
            <Button type="submit" :disabled="processing">{{
                submitLabel
            }}</Button>
        </div>
    </Form>
</template>
