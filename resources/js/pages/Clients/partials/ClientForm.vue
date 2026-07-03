<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { Mail, MapPin, Phone } from '@lucide/vue';
import { ref } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import DateInput from '@/components/ui/date-input/DateInput.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import FormSection from '@/components/ui/form-section/FormSection.vue';
import Input from '@/components/ui/input/Input.vue';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';
import Select from '@/components/ui/select/Select.vue';
import { index as clientsIndex } from '@/routes/clients';
import type { RouteFormDefinition } from '@/wayfinder';

interface ClientFormValues {
    first_name: string;
    middle_name: string | null;
    last_name: string;
    mothers_name: string | null;
    date_of_birth: string;
    gender: string;
    phone: string;
    email: string | null;
    street: string | null;
    building_floor: string | null;
    city: string | null;
    state: string | null;
    country_id: number | null;
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
    client?: ClientFormValues;
    defaultCountryId?: number | null;
    route: RouteFormDefinition<'post'>;
    submitLabel: string;
}>();

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
const city = ref(props.client?.city ?? '');
const state = ref(props.client?.state ?? '');
const countryId = ref(
    props.client?.country_id ? String(props.client.country_id) : props.defaultCountryId ? String(props.defaultCountryId) : '',
);
const emergencyContactName = ref(props.client?.emergency_contact_name ?? '');
const emergencyContactRelationship = ref(props.client?.emergency_contact_relationship ?? '');
const emergencyContactPhone = ref(props.client?.emergency_contact_phone ?? '');
const enrollmentDate = ref(props.client?.enrollment_date ?? '');
const leadSource = ref(props.client?.lead_source ?? '');
</script>

<template>
    <Form v-bind="route" v-slot="{ errors, processing }" class="mx-auto flex w-full max-w-[1100px] flex-col gap-4">
        <FormSection title="Personal information" subtitle="Legal name as it appears on policy documents.">
            <div class="grid grid-cols-3 gap-4">
                <FormField label="First name" for="first_name" required :error="errors.first_name">
                    <Input id="first_name" v-model="firstName" name="first_name" />
                </FormField>
                <FormField label="Middle name" for="middle_name" optional :error="errors.middle_name">
                    <Input id="middle_name" v-model="middleName" name="middle_name" />
                </FormField>
                <FormField label="Last name" for="last_name" required :error="errors.last_name">
                    <Input id="last_name" v-model="lastName" name="last_name" />
                </FormField>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <FormField label="Mother's name" for="mothers_name" optional helper="Used by some carriers as a verification field." :error="errors.mothers_name">
                    <Input id="mothers_name" v-model="mothersName" name="mothers_name" />
                </FormField>
                <FormField label="Date of birth" required :error="errors.date_of_birth">
                    <DateInput v-model="dateOfBirth" name="date_of_birth" />
                </FormField>
            </div>
            <FormField label="Gender" required :error="errors.gender">
                <RadioChips v-model="gender" name="gender" :options="genders" />
            </FormField>
        </FormSection>

        <div class="grid grid-cols-2 gap-4">
            <FormSection title="Contact" subtitle="At least one of phone or email is required.">
                <FormField label="Phone number" for="phone" required :error="errors.phone">
                    <Input id="phone" v-model="phone" name="phone" type="tel">
                        <template #leading>
                            <Phone />
                        </template>
                    </Input>
                </FormField>
                <FormField label="Email" for="email" optional :error="errors.email">
                    <Input id="email" v-model="email" name="email" type="email">
                        <template #leading>
                            <Mail />
                        </template>
                    </Input>
                </FormField>
                <FormField label="Street" for="street" optional :error="errors.street">
                    <Input id="street" v-model="street" name="street">
                        <template #leading>
                            <MapPin />
                        </template>
                    </Input>
                </FormField>
                <FormField label="Building / Floor" for="building_floor" optional :error="errors.building_floor">
                    <Input id="building_floor" v-model="buildingFloor" name="building_floor" />
                </FormField>
                <div class="grid grid-cols-2 gap-4">
                    <FormField label="City" for="city" optional :error="errors.city">
                        <Input id="city" v-model="city" name="city" />
                    </FormField>
                    <FormField label="Governorate" for="state" optional :error="errors.state">
                        <Select id="state" v-model="state" name="state" placeholder="Select">
                            <option value="Beirut">Beirut</option>
                            <option value="Mount Lebanon">Mount Lebanon</option>
                            <option value="North">North</option>
                            <option value="South">South</option>
                            <option value="Nabatieh">Nabatieh</option>
                            <option value="Bekaa">Bekaa</option>
                            <option value="Akkar">Akkar</option>
                            <option value="Baalbek-Hermel">Baalbek-Hermel</option>
                        </Select>
                    </FormField>
                </div>
                <FormField label="Country" for="country_id" optional :error="errors.country_id">
                    <Select id="country_id" v-model="countryId" name="country_id" placeholder="Select">
                        <option v-for="country in countries" :key="country.id" :value="country.id">
                            {{ country.name }}
                        </option>
                    </Select>
                </FormField>
            </FormSection>

            <FormSection title="Emergency contact" subtitle="Recommended for senior and Medicare clients.">
                <FormField label="Full name" for="emergency_contact_name" optional :error="errors.emergency_contact_name">
                    <Input id="emergency_contact_name" v-model="emergencyContactName" name="emergency_contact_name" />
                </FormField>
                <FormField label="Relationship" for="emergency_contact_relationship" optional :error="errors.emergency_contact_relationship">
                    <Select
                        id="emergency_contact_relationship"
                        v-model="emergencyContactRelationship"
                        name="emergency_contact_relationship"
                        placeholder="Select"
                    >
                        <option v-for="rel in emergencyContactRelationships" :key="rel.value" :value="rel.value">
                            {{ rel.label }}
                        </option>
                    </Select>
                </FormField>
                <FormField label="Phone number" for="emergency_contact_phone" optional :error="errors.emergency_contact_phone">
                    <Input id="emergency_contact_phone" v-model="emergencyContactPhone" name="emergency_contact_phone" type="tel" />
                </FormField>
            </FormSection>
        </div>

        <FormSection title="Enrollment" subtitle="When this client joined your book and how they found you.">
            <div class="grid grid-cols-2 gap-4">
                <FormField label="Enrollment date" required :error="errors.enrollment_date">
                    <DateInput v-model="enrollmentDate" name="enrollment_date" />
                </FormField>
                <FormField label="Lead source" for="lead_source" required :error="errors.lead_source">
                    <Select id="lead_source" v-model="leadSource" name="lead_source" placeholder="Select">
                        <option v-for="source in leadSources" :key="source.value" :value="source.value">
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
            <Button type="submit" :disabled="processing">{{ submitLabel }}</Button>
        </div>
    </Form>
</template>
