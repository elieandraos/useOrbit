<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Mail, MapPin, Phone } from '@lucide/vue';
import { ref } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import DateInput from '@/components/ui/date-input/DateInput.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import FormSection from '@/components/ui/form-section/FormSection.vue';
import Input from '@/components/ui/input/Input.vue';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';
import Select from '@/components/ui/select/Select.vue';
import { index as clientsIndex, store as clientsStore } from '@/routes/clients';

defineProps<{
    countries: { id: number; name: string }[];
    genders: { label: string; value: string }[];
    leadSources: { label: string; value: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Clients',
                href: clientsIndex(),
            },
            {
                title: 'Create',
            },
        ],
    },
});

const gender = ref('female');
</script>

<template>
    <Head title="Add new client" />

    <Form v-bind="clientsStore.form()" v-slot="{ errors, processing }" class="flex flex-1 flex-col gap-4 max-w-[1100px]">
        <div class="flex flex-col gap-0.5">
            <h1 class="text-xl font-semibold text-primary">Add new client</h1>
            <p class="text-sm text-tertiary">Capture personal details, contact info, and how you met. You can add policies after creating the client.</p>
        </div>

        <FormSection title="Personal information" subtitle="Legal name as it appears on policy documents.">
            <div class="grid grid-cols-3 gap-4">
                <FormField label="First name" for="first_name" required :error="errors.first_name">
                    <Input id="first_name" name="first_name" />
                </FormField>
                <FormField label="Middle name" for="middle_name" optional :error="errors.middle_name">
                    <Input id="middle_name" name="middle_name" />
                </FormField>
                <FormField label="Last name" for="last_name" required :error="errors.last_name">
                    <Input id="last_name" name="last_name" />
                </FormField>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <FormField label="Mother's name" for="mothers_name" optional helper="Used by some carriers as a verification field." :error="errors.mothers_name">
                    <Input id="mothers_name" name="mothers_name" />
                </FormField>
                <FormField label="Date of birth" required :error="errors.date_of_birth">
                    <DateInput name="date_of_birth" />
                </FormField>
            </div>
            <FormField label="Gender" required :error="errors.gender">
                <RadioChips v-model="gender" name="gender" :options="genders" />
            </FormField>
        </FormSection>

        <div class="grid grid-cols-2 gap-4">
            <FormSection title="Contact" subtitle="At least one of phone or email is required.">
                <FormField label="Phone number" for="phone" required :error="errors.phone">
                    <Input id="phone" name="phone" type="tel">
                        <template #leading>
                            <Phone />
                        </template>
                    </Input>
                </FormField>
                <FormField label="Email" for="email" optional :error="errors.email">
                    <Input id="email" name="email" type="email">
                        <template #leading>
                            <Mail />
                        </template>
                    </Input>
                </FormField>
                <FormField label="Street" for="street" optional :error="errors.street">
                    <Input id="street" name="street">
                        <template #leading>
                            <MapPin />
                        </template>
                    </Input>
                </FormField>
                <FormField label="Building / Floor" for="building_floor" optional :error="errors.building_floor">
                    <Input id="building_floor" name="building_floor" />
                </FormField>
                <div class="grid grid-cols-2 gap-4">
                    <FormField label="City" for="city" optional :error="errors.city">
                        <Input id="city" name="city" />
                    </FormField>
                    <FormField label="Governorate" for="state" optional :error="errors.state">
                        <Select id="state" name="state" placeholder="Select">
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
                    <Select id="country_id" name="country_id" placeholder="Select">
                        <option v-for="country in countries" :key="country.id" :value="country.id">
                            {{ country.name }}
                        </option>
                    </Select>
                </FormField>
            </FormSection>

            <FormSection title="Emergency contact" subtitle="Recommended for senior and Medicare clients.">
                <FormField label="Full name" for="emergency_contact_name" optional :error="errors.emergency_contact_name">
                    <Input id="emergency_contact_name" name="emergency_contact_name" />
                </FormField>
                <FormField label="Relationship" for="emergency_contact_relationship" optional :error="errors.emergency_contact_relationship">
                    <Select id="emergency_contact_relationship" name="emergency_contact_relationship" placeholder="Select">
                        <option value="Spouse">Spouse</option>
                        <option value="Parent">Parent</option>
                        <option value="Child">Child</option>
                        <option value="Sibling">Sibling</option>
                        <option value="Friend">Friend</option>
                        <option value="Other">Other</option>
                    </Select>
                </FormField>
                <FormField label="Phone number" for="emergency_contact_phone" optional :error="errors.emergency_contact_phone">
                    <Input id="emergency_contact_phone" name="emergency_contact_phone" type="tel" />
                </FormField>
            </FormSection>
        </div>

        <FormSection title="Enrollment" subtitle="When this client joined your book and how they found you.">
            <div class="grid grid-cols-2 gap-4">
                <FormField label="Enrollment date" required :error="errors.enrollment_date">
                    <DateInput name="enrollment_date" />
                </FormField>
                <FormField label="Lead source" for="lead_source" required :error="errors.lead_source">
                    <Select id="lead_source" name="lead_source" placeholder="Select">
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
            <Button type="submit" :disabled="processing">Create client</Button>
        </div>
    </Form>
</template>
