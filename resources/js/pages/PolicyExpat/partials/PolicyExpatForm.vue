<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import DateInput from '@/components/ui/date-input/DateInput.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import FormSection from '@/components/ui/form-section/FormSection.vue';
import Input from '@/components/ui/input/Input.vue';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';
import Select from '@/components/ui/select/Select.vue';
import { Typeahead } from '@/components/ui/typeahead';
import type { TypeaheadOption } from '@/components/ui/typeahead';
import { index as policiesIndex } from '@/routes/policies';
import type { RouteFormDefinition } from '@/wayfinder';

interface Option {
    label: string;
    value: string;
}

interface EntityOption {
    id: number;
    full_name?: string;
    name?: string;
}

interface CountryOption {
    id: number;
    name: string;
}

interface PolicyExpatFormValues {
    policy_number: string | null;
    subclass: string;
    type: string;
    client_id: number;
    carrier_id: number;
    agent_id: number | null;
    effective_date: string;
    expiry_date: string;
    premium_amount: string;
    discount_amount: string | null;
    status: string;
    source: string;
    details: {
        coverage_zone: string;
        travel_scope: string | null;
        full_name: string;
        gender: string;
        nationality: string;
        date_of_birth: string;
        phone: string;
        country_id: number | null;
        visa_expiry_date: string | null;
    };
}

const props = defineProps<{
    clients: EntityOption[];
    carriers: EntityOption[];
    agents: EntityOption[];
    types: Option[];
    statuses: Option[];
    sources: Option[];
    coverageZones: Option[];
    genders: Option[];
    countries: CountryOption[];
    policy?: PolicyExpatFormValues;
    defaults?: Record<string, string>;
    route: RouteFormDefinition<'post'>;
    submitLabel: string;
}>();

const policyNumber = ref(props.policy?.policy_number ?? '');
const subclass = ref(
    props.policy?.subclass ?? props.coverageZones[0]?.label ?? '',
);
const type = ref(
    props.policy?.type ??
        props.defaults?.type ??
        props.types[0]?.value ??
        'single',
);
const clientId = ref<number | null>(
    props.policy?.client_id ??
        (props.defaults?.client_id ? Number(props.defaults.client_id) : null),
);
const carrierId = ref<number | null>(
    props.policy?.carrier_id ??
        (props.defaults?.carrier_id ? Number(props.defaults.carrier_id) : null),
);
const agentId = ref<number | null>(
    props.policy?.agent_id ??
        (props.defaults?.agent_id ? Number(props.defaults.agent_id) : null),
);
const effectiveDate = ref(props.policy?.effective_date ?? '');
const expiryDate = ref(props.policy?.expiry_date ?? '');
const premiumAmount = ref(props.policy?.premium_amount ?? '');
const discountAmount = ref(props.policy?.discount_amount ?? '');
const status = ref(
    props.policy?.status ??
        props.defaults?.status ??
        props.statuses[0]?.value ??
        'active',
);
const source = ref(props.policy?.source ?? props.defaults?.source ?? '');

const travelScope = ref(props.policy?.details.travel_scope ?? '');
const fullName = ref(props.policy?.details.full_name ?? '');
const gender = ref(
    props.policy?.details.gender ?? props.genders[0]?.value ?? '',
);
const nationality = ref(props.policy?.details.nationality ?? '');
const dateOfBirth = ref(props.policy?.details.date_of_birth ?? '');
const phone = ref(props.policy?.details.phone ?? '');
const countryId = ref<number | null>(props.policy?.details.country_id ?? null);
const visaExpiryDate = ref(props.policy?.details.visa_expiry_date ?? '');

const coverageZone = computed(
    () =>
        props.coverageZones.find((zone) => zone.label === subclass.value)
            ?.value ??
        props.coverageZones[0]?.value ??
        '',
);
const isInOut = computed(() => coverageZone.value === 'in_out');

const clientOptions = computed<TypeaheadOption[]>(() =>
    props.clients.map((client) => ({
        value: client.id,
        label: client.full_name ?? '',
    })),
);
const carrierOptions = computed<TypeaheadOption[]>(() =>
    props.carriers.map((carrier) => ({
        value: carrier.id,
        label: carrier.name ?? '',
    })),
);
const agentOptions = computed<TypeaheadOption[]>(() =>
    props.agents.map((agent) => ({
        value: agent.id,
        label: agent.full_name ?? '',
    })),
);
const countryOptions = computed<TypeaheadOption[]>(() =>
    props.countries.map((country) => ({
        value: country.id,
        label: country.name,
    })),
);
</script>

<template>
    <Form
        v-bind="route"
        v-slot="{ errors, processing }"
        class="mx-auto flex w-full max-w-[1100px] flex-col gap-4"
    >
        <input type="hidden" name="class" value="expat" />
        <input
            type="hidden"
            name="expat[coverage_zone]"
            :value="coverageZone"
        />

        <FormSection
            title="Coverage"
            subtitle="The policy number and the kind of Expat coverage."
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Policy number"
                    for="policy_number"
                    optional
                    helper="Auto-generated if left blank."
                    :error="errors.policy_number"
                >
                    <Input
                        id="policy_number"
                        v-model="policyNumber"
                        name="policy_number"
                    />
                </FormField>
                <FormField label="Policy type" required :error="errors.type">
                    <RadioChips v-model="type" name="type" :options="types" />
                </FormField>
            </div>
            <FormField label="Sub-class" required :error="errors.subclass">
                <RadioChips
                    v-model="subclass"
                    name="subclass"
                    :options="coverageZones.map((zone) => zone.label)"
                />
            </FormField>
        </FormSection>

        <FormSection
            title="Expat coverage"
            subtitle="The person covered by this policy."
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Full name"
                    for="expat_full_name"
                    required
                    :error="errors['expat.full_name']"
                >
                    <Input
                        id="expat_full_name"
                        v-model="fullName"
                        name="expat[full_name]"
                    />
                </FormField>
                <FormField
                    label="Gender"
                    required
                    :error="errors['expat.gender']"
                >
                    <RadioChips
                        v-model="gender"
                        name="expat[gender]"
                        :options="genders"
                    />
                </FormField>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Nationality"
                    for="expat_nationality"
                    required
                    :error="errors['expat.nationality']"
                >
                    <Input
                        id="expat_nationality"
                        v-model="nationality"
                        name="expat[nationality]"
                    />
                </FormField>
                <FormField
                    label="Date of birth"
                    required
                    :error="errors['expat.date_of_birth']"
                >
                    <DateInput
                        v-model="dateOfBirth"
                        name="expat[date_of_birth]"
                    />
                </FormField>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Phone"
                    for="expat_phone"
                    required
                    :error="errors['expat.phone']"
                >
                    <Input
                        id="expat_phone"
                        v-model="phone"
                        name="expat[phone]"
                    />
                </FormField>
                <FormField
                    label="Country"
                    optional
                    :error="errors['expat.country_id']"
                >
                    <Typeahead
                        v-model="countryId"
                        name="expat[country_id]"
                        :options="countryOptions"
                        placeholder="Select country"
                    />
                </FormField>
            </div>
            <FormField
                label="Visa expiry date"
                optional
                :error="errors['expat.visa_expiry_date']"
            >
                <DateInput
                    v-model="visaExpiryDate"
                    name="expat[visa_expiry_date]"
                />
            </FormField>
            <FormField
                v-if="isInOut"
                label="Travel scope"
                for="expat_travel_scope"
                required
                :error="errors['expat.travel_scope']"
            >
                <Input
                    id="expat_travel_scope"
                    v-model="travelScope"
                    name="expat[travel_scope]"
                />
            </FormField>
        </FormSection>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <FormSection
                title="Parties"
                subtitle="Who the policy belongs to and who's underwriting it."
            >
                <FormField label="Client" required :error="errors.client_id">
                    <Typeahead
                        v-model="clientId"
                        name="client_id"
                        :options="clientOptions"
                        placeholder="Select client"
                    />
                </FormField>
                <FormField
                    label="Insurance company"
                    required
                    :error="errors.carrier_id"
                >
                    <Typeahead
                        v-model="carrierId"
                        name="carrier_id"
                        :options="carrierOptions"
                        placeholder="Select carrier"
                    />
                </FormField>
                <FormField label="Agent" optional :error="errors.agent_id">
                    <Typeahead
                        v-model="agentId"
                        name="agent_id"
                        :options="agentOptions"
                        placeholder="Select agent"
                    />
                </FormField>
            </FormSection>

            <FormSection
                title="Coverage period & status"
                subtitle="Effective dates and current standing."
            >
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField
                        label="Effective date"
                        required
                        :error="errors.effective_date"
                    >
                        <DateInput
                            v-model="effectiveDate"
                            name="effective_date"
                        />
                    </FormField>
                    <FormField
                        label="Expiry date"
                        required
                        :error="errors.expiry_date"
                    >
                        <DateInput v-model="expiryDate" name="expiry_date" />
                    </FormField>
                </div>
                <FormField label="Status" required :error="errors.status">
                    <RadioChips
                        v-model="status"
                        name="status"
                        :options="statuses"
                    />
                </FormField>
                <FormField
                    label="Lead source"
                    for="source"
                    required
                    :error="errors.source"
                >
                    <Select
                        id="source"
                        v-model="source"
                        name="source"
                        placeholder="Select"
                    >
                        <option
                            v-for="sourceOption in sources"
                            :key="sourceOption.value"
                            :value="sourceOption.value"
                        >
                            {{ sourceOption.label }}
                        </option>
                    </Select>
                </FormField>
            </FormSection>
        </div>

        <FormSection
            title="Financials"
            subtitle="Premium and any discount applied."
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Premium amount"
                    for="premium_amount"
                    required
                    :error="errors.premium_amount"
                >
                    <Input
                        id="premium_amount"
                        v-model="premiumAmount"
                        name="premium_amount"
                        type="number"
                        min="0"
                        step="0.01"
                    />
                </FormField>
                <FormField
                    label="Discount amount"
                    for="discount_amount"
                    optional
                    :error="errors.discount_amount"
                >
                    <Input
                        id="discount_amount"
                        v-model="discountAmount"
                        name="discount_amount"
                        type="number"
                        min="0"
                        step="0.01"
                    />
                </FormField>
            </div>
        </FormSection>

        <div class="flex justify-end gap-3">
            <Link :href="policiesIndex().url">
                <Button type="button" variant="ghost">Cancel</Button>
            </Link>
            <Button type="submit" :disabled="processing">{{
                submitLabel
            }}</Button>
        </div>
    </Form>
</template>
