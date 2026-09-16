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

interface InsuredValues {
    id?: number;
    full_name: string;
    relationship: string;
    date_of_birth: string;
    gender: string | null;
    medical_notes: string | null;
}

interface PolicyMedicalFormValues {
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
        coverage_scope: string;
        class_tier: string;
        co_insurance: boolean;
        co_insurance_share: string | null;
        guaranteed_renewable: boolean;
        insured_full_name: string | null;
        insured_date_of_birth: string | null;
        insured_gender: string | null;
        insured_smoker: boolean | null;
        insured_medical_history: string | null;
    };
    insureds: InsuredValues[];
}

const props = defineProps<{
    clients: EntityOption[];
    carriers: EntityOption[];
    agents: EntityOption[];
    types: Option[];
    statuses: Option[];
    sources: Option[];
    coverageScopes: Option[];
    classTiers: Option[];
    genders: Option[];
    policy?: PolicyMedicalFormValues;
    defaults?: Record<string, string>;
    route: RouteFormDefinition<'post'>;
    submitLabel: string;
}>();

const SUBCLASSES = [
    'Hospitalization',
    'Outpatient',
    'Dental',
    'Vision',
    'Major medical',
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

const coverageScope = ref(
    props.policy?.details.coverage_scope ??
        props.coverageScopes[0]?.value ??
        '',
);
const classTier = ref(
    props.policy?.details.class_tier ?? props.classTiers[0]?.value ?? '',
);
const coInsurance = ref(props.policy?.details.co_insurance ? '1' : '0');
const coInsuranceShare = ref(props.policy?.details.co_insurance_share ?? '');
const guaranteedRenewable = ref(
    props.policy?.details.guaranteed_renewable ? '1' : '0',
);

const insuredFullName = ref(props.policy?.details.insured_full_name ?? '');
const insuredDateOfBirth = ref(
    props.policy?.details.insured_date_of_birth ?? '',
);
const insuredGender = ref(
    props.policy?.details.insured_gender ?? props.genders[0]?.value ?? '',
);
const insuredSmoker = ref(props.policy?.details.insured_smoker ? '1' : '0');
const insuredMedicalHistory = ref(
    props.policy?.details.insured_medical_history ?? '',
);

interface InsuredRow {
    key: number;
    id?: number;
    full_name: string;
    relationship: string;
    date_of_birth: string;
    gender: string;
    medical_notes: string;
}

let nextRowKey = 0;

function makeRow(insured?: InsuredValues): InsuredRow {
    nextRowKey += 1;

    return {
        key: nextRowKey,
        id: insured?.id,
        full_name: insured?.full_name ?? '',
        relationship: insured?.relationship ?? '',
        date_of_birth: insured?.date_of_birth ?? '',
        gender: insured?.gender ?? '',
        medical_notes: insured?.medical_notes ?? '',
    };
}

const insuredRows = ref<InsuredRow[]>(
    (props.policy?.insureds ?? []).map((insured) => makeRow(insured)),
);

function addRow() {
    insuredRows.value.push(makeRow());
}

function removeRow(index: number) {
    insuredRows.value.splice(index, 1);
}

const isGroup = computed(() => type.value === 'group');

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

const yesNo: Option[] = [
    { label: 'Yes', value: '1' },
    { label: 'No', value: '0' },
];
</script>

<template>
    <Form
        v-bind="route"
        v-slot="{ errors, processing }"
        class="mx-auto flex w-full max-w-[1100px] flex-col gap-4"
    >
        <input type="hidden" name="class" value="medical" />

        <FormSection
            title="Coverage"
            subtitle="The policy number and the kind of Medical coverage."
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
            title="Medical coverage"
            subtitle="Coverage scope, class tier, and renewal terms."
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Coverage scope"
                    required
                    :error="errors['medical.coverage_scope']"
                >
                    <RadioChips
                        v-model="coverageScope"
                        name="medical[coverage_scope]"
                        :options="coverageScopes"
                    />
                </FormField>
                <FormField
                    label="Class"
                    required
                    :error="errors['medical.class_tier']"
                >
                    <RadioChips
                        v-model="classTier"
                        name="medical[class_tier]"
                        :options="classTiers"
                    />
                </FormField>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Co-insurance"
                    required
                    :error="errors['medical.co_insurance']"
                >
                    <RadioChips
                        v-model="coInsurance"
                        name="medical[co_insurance]"
                        :options="yesNo"
                    />
                </FormField>
                <FormField
                    v-if="coInsurance === '1'"
                    label="Co-insurance share (%)"
                    for="medical_co_insurance_share"
                    required
                    :error="errors['medical.co_insurance_share']"
                >
                    <Input
                        id="medical_co_insurance_share"
                        v-model="coInsuranceShare"
                        name="medical[co_insurance_share]"
                        type="number"
                        min="0"
                        max="100"
                    />
                </FormField>
            </div>
            <FormField
                label="Guaranteed renewable"
                required
                :error="errors['medical.guaranteed_renewable']"
            >
                <RadioChips
                    v-model="guaranteedRenewable"
                    name="medical[guaranteed_renewable]"
                    :options="yesNo"
                />
            </FormField>
        </FormSection>

        <FormSection
            v-if="!isGroup"
            title="Insured profile"
            subtitle="The individual covered by this policy."
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Full name"
                    for="medical_insured_full_name"
                    required
                    :error="errors['medical.insured_full_name']"
                >
                    <Input
                        id="medical_insured_full_name"
                        v-model="insuredFullName"
                        name="medical[insured_full_name]"
                    />
                </FormField>
                <FormField
                    label="Date of birth"
                    required
                    :error="errors['medical.insured_date_of_birth']"
                >
                    <DateInput
                        v-model="insuredDateOfBirth"
                        name="medical[insured_date_of_birth]"
                    />
                </FormField>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Gender"
                    required
                    :error="errors['medical.insured_gender']"
                >
                    <RadioChips
                        v-model="insuredGender"
                        name="medical[insured_gender]"
                        :options="genders"
                    />
                </FormField>
                <FormField
                    label="Smoker"
                    required
                    :error="errors['medical.insured_smoker']"
                >
                    <RadioChips
                        v-model="insuredSmoker"
                        name="medical[insured_smoker]"
                        :options="yesNo"
                    />
                </FormField>
            </div>
            <FormField
                label="Medical history"
                for="medical_insured_medical_history"
                optional
                :error="errors['medical.insured_medical_history']"
            >
                <Input
                    id="medical_insured_medical_history"
                    v-model="insuredMedicalHistory"
                    name="medical[insured_medical_history]"
                />
            </FormField>
        </FormSection>

        <FormSection
            v-else
            title="Covered members"
            subtitle="Everyone covered under this group policy."
        >
            <div v-if="insuredRows.length === 0" class="text-sm text-tertiary">
                No covered members yet. Add at least one member below.
            </div>

            <div
                v-for="(row, index) in insuredRows"
                :key="row.key"
                class="flex flex-col gap-4 rounded-[10px] border border-border p-4"
            >
                <input
                    v-if="row.id"
                    type="hidden"
                    :name="`insureds[${index}][id]`"
                    :value="row.id"
                />

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-primary"
                        >Member {{ index + 1 }}</span
                    >
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="removeRow(index)"
                        >Remove</Button
                    >
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField
                        label="Full name"
                        required
                        :error="errors[`insureds.${index}.full_name`]"
                    >
                        <Input
                            v-model="row.full_name"
                            :name="`insureds[${index}][full_name]`"
                        />
                    </FormField>
                    <FormField
                        label="Relationship"
                        required
                        :error="errors[`insureds.${index}.relationship`]"
                    >
                        <Input
                            v-model="row.relationship"
                            :name="`insureds[${index}][relationship]`"
                        />
                    </FormField>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField
                        label="Date of birth"
                        required
                        :error="errors[`insureds.${index}.date_of_birth`]"
                    >
                        <DateInput
                            v-model="row.date_of_birth"
                            :name="`insureds[${index}][date_of_birth]`"
                        />
                    </FormField>
                    <FormField
                        label="Gender"
                        optional
                        :error="errors[`insureds.${index}.gender`]"
                    >
                        <RadioChips
                            v-model="row.gender"
                            :name="`insureds[${index}][gender]`"
                            :options="genders"
                        />
                    </FormField>
                </div>
                <FormField
                    label="Medical notes"
                    optional
                    :error="errors[`insureds.${index}.medical_notes`]"
                >
                    <Input
                        v-model="row.medical_notes"
                        :name="`insureds[${index}][medical_notes]`"
                    />
                </FormField>
            </div>

            <Button type="button" variant="secondary" @click="addRow"
                >Add covered member</Button
            >
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
