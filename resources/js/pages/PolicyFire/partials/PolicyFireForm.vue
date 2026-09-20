<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
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

interface PolicyFireFormValues {
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
        property_type: string;
        floor_area: number;
        year_built: number | null;
        street: string;
        building_floor: string | null;
        city: string;
        state_id: number | null;
        state_name: string | null;
        country_id: number | null;
        sum_insured: string;
    };
}

const props = defineProps<{
    clients: EntityOption[];
    carriers: EntityOption[];
    agents: EntityOption[];
    types: Option[];
    statuses: Option[];
    sources: Option[];
    countries: CountryOption[];
    policy?: PolicyFireFormValues;
    defaults?: Record<string, string>;
    route: RouteFormDefinition<'post'>;
    submitLabel: string;
}>();

const SUBCLASSES = [
    'Building',
    'Contents',
    'Business interruption',
    'All risk',
];

const policyNumber = ref(props.policy?.policy_number ?? '');
const subclass = ref(props.policy?.subclass ?? SUBCLASSES[0]);
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

const propertyType = ref(props.policy?.details.property_type ?? '');
const floorArea = ref(
    props.policy?.details.floor_area
        ? `${props.policy.details.floor_area}`
        : '',
);
const yearBuilt = ref(
    props.policy?.details.year_built
        ? `${props.policy.details.year_built}`
        : '',
);
const street = ref(props.policy?.details.street ?? '');
const buildingFloor = ref(props.policy?.details.building_floor ?? '');
const city = ref(props.policy?.details.city ?? '');
const countryId = ref<number | null>(props.policy?.details.country_id ?? null);
const stateId = ref<number | null>(props.policy?.details.state_id ?? null);
const sumInsured = ref(props.policy?.details.sum_insured ?? '');

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
        <input type="hidden" name="class" value="fire" />

        <FormSection
            title="Coverage"
            subtitle="The policy number and the kind of Fire coverage."
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
                    :options="SUBCLASSES"
                />
            </FormField>
        </FormSection>

        <FormSection
            title="Fire coverage"
            subtitle="The property covered by this policy."
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Property type"
                    for="fire_property_type"
                    required
                    :error="errors['fire.property_type']"
                >
                    <Input
                        id="fire_property_type"
                        v-model="propertyType"
                        name="fire[property_type]"
                    />
                </FormField>
                <FormField
                    label="Floor area (m²)"
                    for="fire_floor_area"
                    required
                    :error="errors['fire.floor_area']"
                >
                    <Input
                        id="fire_floor_area"
                        v-model="floorArea"
                        name="fire[floor_area]"
                        type="number"
                        min="1"
                    />
                </FormField>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Year built"
                    for="fire_year_built"
                    optional
                    :error="errors['fire.year_built']"
                >
                    <Input
                        id="fire_year_built"
                        v-model="yearBuilt"
                        name="fire[year_built]"
                        type="number"
                        min="1900"
                    />
                </FormField>
                <FormField
                    label="Sum insured"
                    for="fire_sum_insured"
                    required
                    :error="errors['fire.sum_insured']"
                >
                    <Input
                        id="fire_sum_insured"
                        v-model="sumInsured"
                        name="fire[sum_insured]"
                        type="number"
                        min="0"
                        step="0.01"
                    />
                </FormField>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Street"
                    for="fire_street"
                    required
                    :error="errors['fire.street']"
                >
                    <Input
                        id="fire_street"
                        v-model="street"
                        name="fire[street]"
                    />
                </FormField>
                <FormField
                    label="Building / Floor"
                    for="fire_building_floor"
                    optional
                    :error="errors['fire.building_floor']"
                >
                    <Input
                        id="fire_building_floor"
                        v-model="buildingFloor"
                        name="fire[building_floor]"
                    />
                </FormField>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="City"
                    for="fire_city"
                    required
                    :error="errors['fire.city']"
                >
                    <Input id="fire_city" v-model="city" name="fire[city]" />
                </FormField>
                <FormField
                    label="Country"
                    required
                    :error="errors['fire.country_id']"
                >
                    <Typeahead
                        v-model="countryId"
                        name="fire[country_id]"
                        :options="countryOptions"
                        placeholder="Select country"
                    />
                </FormField>
            </div>
            <FormField
                label="Governorate"
                required
                :error="errors['fire.state_id']"
            >
                <Typeahead
                    v-model="stateId"
                    name="fire[state_id]"
                    :options="stateOptions"
                    :loading="stateLoading"
                    :initial-label="policy?.details.state_name"
                    placeholder="Select"
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
