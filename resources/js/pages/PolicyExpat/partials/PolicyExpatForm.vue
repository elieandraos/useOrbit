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
import { usePolicyCurrency } from '@/composables/usePolicyCurrency';
import { policyDateEndYear } from '@/lib/policyDateEndYear';
import PolicyEntrySummary from '@/pages/Policies/partials/PolicyEntrySummary.vue';
import PolicyFinancialsSection from '@/pages/Policies/partials/PolicyFinancialsSection.vue';
import PolicyPartiesSection from '@/pages/Policies/partials/PolicyPartiesSection.vue';
import { index as policiesIndex } from '@/routes/policies';
import type {
    PolicyCurrencyOption,
    PolicyEntry,
    PolicyParties,
} from '@/types/policy';
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

interface PolicyExpatFormValues extends PolicyParties {
    policy_number: string | null;
    subclass: string;
    type: string;
    effective_date: string;
    expiry_date: string;
    premium_amount: string;
    discount_amount: string | null;
    currency_id: number;
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
    subclasses: string[];
    types: Option[];
    sources: Option[];
    currencies: PolicyCurrencyOption[];
    defaultCurrencyId?: number | null;
    coverageZones: Option[];
    genders: Option[];
    countries: CountryOption[];
    policy?: PolicyExpatFormValues;
    /** On a new policy, the first step's choices, summarized instead of asked again. */
    entry?: PolicyEntry;
    route: RouteFormDefinition<'post'>;
    submitLabel: string;
}>();

const { currencyId, currencyCode } = usePolicyCurrency(
    () => props.currencies,
    props.policy?.currency_id,
    props.defaultCurrencyId,
);

const policyNumber = ref(props.policy?.policy_number ?? '');
const subclass = ref(props.policy?.subclass ?? props.subclasses[0] ?? '');
const type = ref(
    props.policy?.type ??
        props.entry?.type.value ??
        props.types[0]?.value ??
        'single',
);
const effectiveDate = ref(props.policy?.effective_date ?? '');
const expiryDate = ref(props.policy?.expiry_date ?? '');
const source = ref(props.policy?.source ?? '');

const coverageZone = ref(
    props.policy?.details.coverage_zone ?? props.coverageZones[0]?.value ?? '',
);
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

const isInOut = computed(() => coverageZone.value === 'in_out');

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

        <PolicyEntrySummary v-if="entry" :entry="entry" :errors="errors" />

        <FormSection
            title="Coverage"
            subtitle="The policy number and the kind of Expat coverage."
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Policy number"
                    for="policy_number"
                    required
                    :error="errors.policy_number"
                >
                    <Input
                        id="policy_number"
                        v-model="policyNumber"
                        name="policy_number"
                    />
                </FormField>
                <FormField
                    v-if="!entry"
                    label="Policy type"
                    required
                    :error="errors.type"
                >
                    <RadioChips v-model="type" name="type" :options="types" />
                </FormField>
            </div>
            <FormField label="Sub-class" required :error="errors.subclass">
                <RadioChips
                    v-model="subclass"
                    name="subclass"
                    :options="subclasses"
                />
            </FormField>
        </FormSection>

        <FormSection
            title="Expat coverage"
            subtitle="The coverage zone and the person covered by this policy."
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Coverage zone"
                    required
                    :error="errors['expat.coverage_zone']"
                >
                    <RadioChips
                        v-model="coverageZone"
                        name="expat[coverage_zone]"
                        :options="coverageZones"
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
            </div>
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
                    :end-year="policyDateEndYear"
                />
            </FormField>
        </FormSection>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <PolicyPartiesSection
                :clients="clients"
                :carriers="carriers"
                :agents="agents"
                :policy="policy"
                :entry="entry"
                :errors="errors"
            />

            <FormSection
                :title="entry ? 'Coverage period' : 'Coverage period & origin'"
                :subtitle="
                    entry
                        ? 'Effective dates.'
                        : 'Effective dates and how this policy came to you.'
                "
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
                            :end-year="policyDateEndYear"
                        />
                    </FormField>
                    <FormField
                        label="Expiry date"
                        required
                        :error="errors.expiry_date"
                    >
                        <DateInput
                            v-model="expiryDate"
                            name="expiry_date"
                            :end-year="policyDateEndYear"
                        />
                    </FormField>
                </div>
                <FormField
                    v-if="!entry"
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

        <PolicyFinancialsSection
            v-model:currency-id="currencyId"
            :currencies="currencies"
            :currency-code="currencyCode"
            :policy="policy"
            :errors="errors"
        />

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
