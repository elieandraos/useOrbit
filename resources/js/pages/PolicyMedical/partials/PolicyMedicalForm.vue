<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import DateInput from '@/components/ui/date-input/DateInput.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import FormSection from '@/components/ui/form-section/FormSection.vue';
import Input from '@/components/ui/input/Input.vue';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';
import Select from '@/components/ui/select/Select.vue';
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
import DiscardTypeDataModal from './DiscardTypeDataModal.vue';

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

interface PolicyMedicalFormValues extends PolicyParties {
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
    insureds?: InsuredValues[];
}

const props = defineProps<{
    carriers: EntityOption[];
    agents: EntityOption[];
    subclasses: string[];
    types: Option[];
    sources: Option[];
    currencies: PolicyCurrencyOption[];
    defaultCurrencyId?: number | null;
    coverageScopes: Option[];
    classTiers: Option[];
    genders: Option[];
    policy?: PolicyMedicalFormValues;
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

interface TypeChangeDiscard {
    title: string;
    description: string;
    discardedItems: string[];
    confirmLabel: string;
}

/**
 * Covered members and the insured profile the policy had when the page loaded.
 * Unsaved form edits don't count: only persisted data is lost on a type change.
 */
const persistedMemberNames = computed(() =>
    props.policy?.type === 'group'
        ? (props.policy.insureds ?? []).map((insured) => insured.full_name)
        : [],
);

const persistedInsuredProfileName = computed(() => {
    if (props.policy?.type !== 'single') {
        return null;
    }

    const details = props.policy.details;
    const hasInsuredProfile = [
        details.insured_full_name,
        details.insured_date_of_birth,
        details.insured_gender,
        details.insured_smoker,
        details.insured_medical_history,
    ].some((value) => value !== null && value !== undefined && value !== '');

    if (!hasInsuredProfile) {
        return null;
    }

    return details.insured_full_name || 'Unnamed insured';
});

const typeChangeDiscard = computed<TypeChangeDiscard | null>(() => {
    if (!props.policy || type.value === props.policy.type) {
        return null;
    }

    const memberCount = persistedMemberNames.value.length;

    if (type.value === 'single' && memberCount > 0) {
        return {
            title: 'Remove covered members?',
            description: `Saving this policy as Single permanently removes its ${memberCount} covered ${memberCount === 1 ? 'member' : 'members'}. This can't be undone.`,
            discardedItems: persistedMemberNames.value,
            confirmLabel: 'Save and remove members',
        };
    }

    if (type.value === 'group' && persistedInsuredProfileName.value) {
        return {
            title: 'Clear the insured profile?',
            description:
                "Saving this policy as Group permanently clears its insured profile: the insured's name, date of birth, gender, smoker status, and medical history. This can't be undone.",
            discardedItems: [persistedInsuredProfileName.value],
            confirmLabel: 'Save and clear profile',
        };
    }

    return null;
});

const isDiscardModalOpen = ref(false);
let isDiscardConfirmed = false;

/**
 * Hold the save while a type change would discard persisted data, until the user confirms it.
 */
function confirmTypeChangeDiscard(): boolean {
    if (!typeChangeDiscard.value || isDiscardConfirmed) {
        isDiscardConfirmed = false;

        return true;
    }

    isDiscardModalOpen.value = true;

    return false;
}

function saveDiscardingTypeData(submit: () => void) {
    isDiscardConfirmed = true;
    isDiscardModalOpen.value = false;
    submit();
}

const yesNo: Option[] = [
    { label: 'Yes', value: '1' },
    { label: 'No', value: '0' },
];

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
        v-slot="{ errors, processing, submit }"
        :on-before="confirmTypeChangeDiscard"
        class="mx-auto flex w-full max-w-[1100px] flex-col gap-4"
    >
        <input type="hidden" name="class" value="medical" />

        <PolicyEntrySummary v-if="entry" :entry="entry" :errors="errors" />

        <FormSection
            title="Coverage"
            subtitle="The policy number and the kind of Medical coverage."
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
                    label="Plan tier"
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
                        step="0.01"
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
                v-if="insuredRows.length > 0"
                class="flex flex-col divide-y divide-border-subtle"
            >
                <div
                    v-for="(row, index) in insuredRows"
                    :key="row.key"
                    class="flex items-start gap-4 py-5 first:pt-0 last:pb-0 sm:gap-6"
                >
                    <input
                        v-if="row.id"
                        type="hidden"
                        :name="`insureds[${index}][id]`"
                        :value="row.id"
                    />

                    <div class="flex min-w-0 flex-1 flex-col gap-4">
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
                                :error="
                                    errors[`insureds.${index}.relationship`]
                                "
                            >
                                <Input
                                    v-model="row.relationship"
                                    :name="`insureds[${index}][relationship]`"
                                    placeholder="e.g. Employee, Spouse, Child"
                                />
                            </FormField>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FormField
                                label="Date of birth"
                                required
                                :error="
                                    errors[`insureds.${index}.date_of_birth`]
                                "
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

                    <button
                        type="button"
                        :title="`Remove ${row.full_name || 'member'}`"
                        :aria-label="`Remove ${row.full_name || 'member'}`"
                        class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-secondary transition-colors outline-none hover:bg-sunken hover:text-danger focus-visible:ring-2 focus-visible:ring-accent-ring"
                        @click="removeRow(index)"
                    >
                        <Trash2 class="size-3.5" />
                    </button>
                </div>
            </div>

            <p v-if="errors.insureds" class="text-xs text-danger">
                {{ errors.insureds }}
            </p>

            <Button
                type="button"
                variant="primary"
                class="self-start"
                @click="addRow"
            >
                <template #leading><Plus /></template>
                Add covered member
            </Button>
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

        <DiscardTypeDataModal
            v-if="typeChangeDiscard"
            v-model:open="isDiscardModalOpen"
            :title="typeChangeDiscard.title"
            :description="typeChangeDiscard.description"
            :discarded-items="typeChangeDiscard.discardedItems"
            :confirm-label="typeChangeDiscard.confirmLabel"
            @confirm="saveDiscardingTypeData(submit)"
        />
    </Form>
</template>
