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
import Textarea from '@/components/ui/textarea/Textarea.vue';
import { usePolicyCurrency } from '@/composables/usePolicyCurrency';
import { endPolicyCreateFlow } from '@/lib/policyCreateFlow';
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

interface PolicyLifeFormValues extends PolicyParties {
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
        sum_assured: string;
        term_years: number;
        smoker: boolean;
        beneficiaries: string;
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
    policy?: PolicyLifeFormValues;
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

const yesNo: Option[] = [
    { label: 'Yes', value: '1' },
    { label: 'No', value: '0' },
];

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

const sumAssured = ref(props.policy?.details.sum_assured ?? '');
const termYears = ref(
    props.policy?.details.term_years
        ? `${props.policy.details.term_years}`
        : '',
);
const smoker = ref(props.policy?.details.smoker ? '1' : '0');
const beneficiaries = ref(props.policy?.details.beneficiaries ?? '');

/**
 * Cancelling a new policy ends the Create flow; cancelling an edit leaves history alone.
 */
function cancel(): void {
    if (props.entry) {
        endPolicyCreateFlow();
    }
}
</script>

<template>
    <Form
        v-bind="route"
        v-slot="{ errors, processing }"
        class="mx-auto flex w-full max-w-[1100px] flex-col gap-4"
    >
        <input type="hidden" name="class" value="life" />

        <PolicyEntrySummary v-if="entry" :entry="entry" :errors="errors" />

        <FormSection
            title="Coverage"
            subtitle="The policy number and the kind of Life coverage."
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
            title="Life coverage"
            subtitle="The sum assured, term, and beneficiaries for this policy."
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Sum assured"
                    for="life_sum_assured"
                    required
                    :error="errors['life.sum_assured']"
                >
                    <Input
                        id="life_sum_assured"
                        v-model="sumAssured"
                        name="life[sum_assured]"
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
                    label="Term (years)"
                    for="life_term_years"
                    required
                    :error="errors['life.term_years']"
                >
                    <Input
                        id="life_term_years"
                        v-model="termYears"
                        name="life[term_years]"
                        type="number"
                        min="1"
                        max="100"
                    />
                </FormField>
            </div>
            <FormField label="Smoker" required :error="errors['life.smoker']">
                <RadioChips
                    v-model="smoker"
                    name="life[smoker]"
                    :options="yesNo"
                />
            </FormField>
            <FormField
                label="Beneficiaries"
                for="life_beneficiaries"
                required
                helper="Free text — e.g. names and their share of the sum assured."
                :error="errors['life.beneficiaries']"
            >
                <Textarea
                    id="life_beneficiaries"
                    v-model="beneficiaries"
                    name="life[beneficiaries]"
                />
            </FormField>
        </FormSection>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <PolicyPartiesSection
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
            <Link :href="policiesIndex().url" @before="cancel">
                <Button type="button" variant="ghost">Cancel</Button>
            </Link>
            <Button type="submit" :disabled="processing">{{
                submitLabel
            }}</Button>
        </div>
    </Form>
</template>
