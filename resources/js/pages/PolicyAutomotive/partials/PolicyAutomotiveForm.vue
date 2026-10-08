<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { computed, ref, toRefs } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import DateInput from '@/components/ui/date-input/DateInput.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import FormSection from '@/components/ui/form-section/FormSection.vue';
import Input from '@/components/ui/input/Input.vue';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';
import Select from '@/components/ui/select/Select.vue';
import { usePolicyCurrency } from '@/composables/usePolicyCurrency';
import {
    initialPolicyCommonEntries,
    usePolicyFormEntries,
} from '@/composables/usePolicyFormEntries';
import {
    endPolicyCreateFlow,
    forgetPolicyCreateFlow,
} from '@/lib/policyCreateFlow';
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

interface PolicyAutomotiveFormValues extends PolicyParties {
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
        plate_number: string;
        make: string;
        model: string;
        year: number;
        vin: string | null;
        color: string | null;
        valuation_amount: string | null;
        valuation_source: string | null;
    };
}

const props = defineProps<{
    carriers: EntityOption[];
    agents: EntityOption[];
    subclasses: string[];
    types: Option[];
    sources: Option[];
    currencies: PolicyCurrencyOption[];
    defaultCurrencyId?: number | null;
    policy?: PolicyAutomotiveFormValues;
    /** On a new policy, the first step's choices, summarized instead of asked again. */
    entry?: PolicyEntry;
    route: RouteFormDefinition<'post'>;
    submitLabel: string;
}>();

const type = ref(
    props.policy?.type ??
        props.entry?.type.value ??
        props.types[0]?.value ??
        'single',
);
const source = ref(props.policy?.source ?? '');

const { entries, carriedWork } = usePolicyFormEntries({
    policyClass: 'automotive',
    entry: props.entry,
    common: initialPolicyCommonEntries(props.policy, props.defaultCurrencyId),
    details: () => ({
        subclass: props.policy?.subclass ?? props.subclasses[0] ?? '',
        plate_number: props.policy?.details.plate_number ?? '',
        make: props.policy?.details.make ?? '',
        model: props.policy?.details.model ?? '',
        year: props.policy?.details.year ? `${props.policy.details.year}` : '',
        vin: props.policy?.details.vin ?? '',
        color: props.policy?.details.color ?? '',
        valuation_amount: props.policy?.details.valuation_amount ?? '',
        valuation_source: props.policy?.details.valuation_source ?? '',
    }),
});

const {
    policy_number: policyNumber,
    carrier_branch_id: carrierBranchId,
    effective_date: effectiveDate,
    expiry_date: expiryDate,
    currency_id: currencyId,
    premium_amount: premiumAmount,
    discount_amount: discountAmount,
} = toRefs(entries.common);

const {
    subclass,
    plate_number: plateNumber,
    make,
    model,
    year,
    vin,
    color,
    valuation_amount: valuationAmount,
    valuation_source: valuationSource,
} = toRefs(entries.details);

const { currencyCode } = usePolicyCurrency(() => props.currencies, currencyId);

// Mirrors the server's `automotive.year` maximum of next year.
const maxVehicleYear = new Date().getFullYear() + 1;

const isAllRisk = computed(() => subclass.value === 'All Risk');

/**
 * Cancelling a new policy ends the Create flow; cancelling an edit leaves history alone.
 */
function cancel(): void {
    if (props.entry) {
        endPolicyCreateFlow();
    }
}

/**
 * Creating the policy ends the Create flow: the server clears its history, and its work is forgotten here.
 */
function forgetCreatedFlow(): void {
    if (props.entry) {
        forgetPolicyCreateFlow();
    }
}
</script>

<template>
    <Form
        v-bind="route"
        :on-success="forgetCreatedFlow"
        v-slot="{ errors, processing }"
        class="mx-auto flex w-full max-w-[1100px] flex-col gap-4"
    >
        <input type="hidden" name="class" value="automotive" />

        <PolicyEntrySummary
            v-if="entry"
            :entry="entry"
            :errors="errors"
            :carried-work="carriedWork"
        />

        <FormSection
            title="Coverage"
            subtitle="The policy number and the kind of Automotive coverage."
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
            title="Automotive coverage"
            subtitle="The vehicle covered by this policy."
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Plate number"
                    for="automotive_plate_number"
                    required
                    :error="errors['automotive.plate_number']"
                >
                    <Input
                        id="automotive_plate_number"
                        v-model="plateNumber"
                        name="automotive[plate_number]"
                    />
                </FormField>
                <FormField
                    label="Make"
                    for="automotive_make"
                    required
                    :error="errors['automotive.make']"
                >
                    <Input
                        id="automotive_make"
                        v-model="make"
                        name="automotive[make]"
                    />
                </FormField>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Model"
                    for="automotive_model"
                    required
                    :error="errors['automotive.model']"
                >
                    <Input
                        id="automotive_model"
                        v-model="model"
                        name="automotive[model]"
                    />
                </FormField>
                <FormField
                    label="Year"
                    for="automotive_year"
                    required
                    :error="errors['automotive.year']"
                >
                    <Input
                        id="automotive_year"
                        v-model="year"
                        name="automotive[year]"
                        type="number"
                        min="1900"
                        :max="maxVehicleYear"
                    />
                </FormField>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="VIN"
                    for="automotive_vin"
                    optional
                    :error="errors['automotive.vin']"
                >
                    <Input
                        id="automotive_vin"
                        v-model="vin"
                        name="automotive[vin]"
                    />
                </FormField>
                <FormField
                    label="Color"
                    for="automotive_color"
                    optional
                    :error="errors['automotive.color']"
                >
                    <Input
                        id="automotive_color"
                        v-model="color"
                        name="automotive[color]"
                    />
                </FormField>
            </div>
            <div v-if="isAllRisk" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Valuation amount"
                    for="automotive_valuation_amount"
                    required
                    :error="errors['automotive.valuation_amount']"
                >
                    <Input
                        id="automotive_valuation_amount"
                        v-model="valuationAmount"
                        name="automotive[valuation_amount]"
                        type="number"
                        min="0"
                        step="0.01"
                    >
                        <template v-if="currencyCode" #leading>
                            <span class="text-xs font-medium text-tertiary">{{
                                currencyCode
                            }}</span>
                        </template>
                    </Input>
                </FormField>
                <FormField
                    label="Valuation source"
                    for="automotive_valuation_source"
                    required
                    :error="errors['automotive.valuation_source']"
                >
                    <Input
                        id="automotive_valuation_source"
                        v-model="valuationSource"
                        name="automotive[valuation_source]"
                    />
                </FormField>
            </div>
        </FormSection>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <PolicyPartiesSection
                :carriers="carriers"
                :agents="agents"
                v-model:carrier-branch-id="carrierBranchId"
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
            v-model:premium-amount="premiumAmount"
            v-model:discount-amount="discountAmount"
            :currencies="currencies"
            :currency-code="currencyCode"
            :errors="errors"
        />

        <div class="flex justify-end gap-3">
            <Link :href="policiesIndex().url" @before="cancel">
                <Button type="button" variant="ghost">Cancel</Button>
            </Link>
            <Button type="submit" :disabled="processing">{{
                submitLabel
            }}</Button>
        </div>
    </Form>
</template>
