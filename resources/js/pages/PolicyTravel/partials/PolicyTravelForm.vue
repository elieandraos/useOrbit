<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import DateInput from '@/components/ui/date-input/DateInput.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import FormSection from '@/components/ui/form-section/FormSection.vue';
import Input from '@/components/ui/input/Input.vue';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';
import Select from '@/components/ui/select/Select.vue';
import { usePolicyCurrency } from '@/composables/usePolicyCurrency';
import { policyDateEndYear } from '@/lib/policyDateEndYear';
import PolicyFinancialsSection from '@/pages/Policies/partials/PolicyFinancialsSection.vue';
import PolicyPartiesSection from '@/pages/Policies/partials/PolicyPartiesSection.vue';
import { index as policiesIndex } from '@/routes/policies';
import type { PolicyCurrencyOption, PolicyParties } from '@/types/policy';
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

interface PolicyTravelFormValues extends PolicyParties {
    policy_number: string | null;
    subclass: string;
    type: string;
    effective_date: string;
    expiry_date: string;
    premium_amount: string;
    discount_amount: string | null;
    currency_id: number;
    status: string;
    source: string;
    details: {
        destination: string;
        trip_start_date: string;
        trip_end_date: string;
        travelers: string;
        coverage_tier: string;
    };
}

const props = defineProps<{
    clients: EntityOption[];
    carriers: EntityOption[];
    agents: EntityOption[];
    subclasses: string[];
    coverageTiers: Option[];
    types: Option[];
    statuses: Option[];
    sources: Option[];
    currencies: PolicyCurrencyOption[];
    defaultCurrencyId?: number | null;
    policy?: PolicyTravelFormValues;
    defaults?: Record<string, string>;
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
        props.defaults?.type ??
        props.types[0]?.value ??
        'single',
);
const effectiveDate = ref(props.policy?.effective_date ?? '');
const expiryDate = ref(props.policy?.expiry_date ?? '');
const status = ref(
    props.policy?.status ??
        props.defaults?.status ??
        props.statuses[0]?.value ??
        'active',
);
const source = ref(props.policy?.source ?? props.defaults?.source ?? '');

const destination = ref(props.policy?.details.destination ?? '');
const tripStartDate = ref(props.policy?.details.trip_start_date ?? '');
const tripEndDate = ref(props.policy?.details.trip_end_date ?? '');
const travelers = ref(props.policy?.details.travelers ?? '');
const coverageTier = ref(
    props.policy?.details.coverage_tier ?? props.coverageTiers[0]?.value ?? '',
);
</script>

<template>
    <Form
        v-bind="route"
        v-slot="{ errors, processing }"
        class="mx-auto flex w-full max-w-[1100px] flex-col gap-4"
    >
        <input type="hidden" name="class" value="travel" />

        <FormSection
            title="Coverage"
            subtitle="The policy number and the kind of Travel coverage."
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
                <FormField label="Policy type" required :error="errors.type">
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
            title="Travel coverage"
            subtitle="The trip covered by this policy."
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Destination"
                    for="travel_destination"
                    required
                    :error="errors['travel.destination']"
                >
                    <Input
                        id="travel_destination"
                        v-model="destination"
                        name="travel[destination]"
                    />
                </FormField>
                <FormField
                    label="Travelers"
                    for="travel_travelers"
                    required
                    :error="errors['travel.travelers']"
                >
                    <Input
                        id="travel_travelers"
                        v-model="travelers"
                        name="travel[travelers]"
                    />
                </FormField>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Trip start date"
                    required
                    :error="errors['travel.trip_start_date']"
                >
                    <DateInput
                        v-model="tripStartDate"
                        name="travel[trip_start_date]"
                        :end-year="policyDateEndYear"
                    />
                </FormField>
                <FormField
                    label="Trip end date"
                    required
                    :error="errors['travel.trip_end_date']"
                >
                    <DateInput
                        v-model="tripEndDate"
                        name="travel[trip_end_date]"
                        :end-year="policyDateEndYear"
                    />
                </FormField>
            </div>
            <FormField
                label="Coverage tier"
                required
                :error="errors['travel.coverage_tier']"
            >
                <RadioChips
                    v-model="coverageTier"
                    name="travel[coverage_tier]"
                    :options="coverageTiers"
                />
            </FormField>
        </FormSection>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <PolicyPartiesSection
                :clients="clients"
                :carriers="carriers"
                :agents="agents"
                :policy="policy"
                :defaults="defaults"
                :errors="errors"
            />

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
