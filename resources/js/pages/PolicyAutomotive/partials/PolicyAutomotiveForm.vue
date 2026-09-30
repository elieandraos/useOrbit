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
import { policyDateEndYear } from '@/lib/policyDateEndYear';
import PolicyPartiesSection from '@/pages/Policies/partials/PolicyPartiesSection.vue';
import { index as policiesIndex } from '@/routes/policies';
import type { PolicyParties } from '@/types/policy';
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
    status: string;
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
    clients: EntityOption[];
    carriers: EntityOption[];
    agents: EntityOption[];
    subclasses: string[];
    types: Option[];
    statuses: Option[];
    sources: Option[];
    policy?: PolicyAutomotiveFormValues;
    defaults?: Record<string, string>;
    route: RouteFormDefinition<'post'>;
    submitLabel: string;
}>();

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
const premiumAmount = ref(props.policy?.premium_amount ?? '');
const discountAmount = ref(props.policy?.discount_amount ?? '');
const status = ref(
    props.policy?.status ??
        props.defaults?.status ??
        props.statuses[0]?.value ??
        'active',
);
const source = ref(props.policy?.source ?? props.defaults?.source ?? '');

const plateNumber = ref(props.policy?.details.plate_number ?? '');
const make = ref(props.policy?.details.make ?? '');
const model = ref(props.policy?.details.model ?? '');
// Mirrors the server's `automotive.year` maximum of next year.
const maxVehicleYear = new Date().getFullYear() + 1;
const year = ref(
    props.policy?.details.year ? `${props.policy.details.year}` : '',
);
const vin = ref(props.policy?.details.vin ?? '');
const color = ref(props.policy?.details.color ?? '');
const valuationAmount = ref(props.policy?.details.valuation_amount ?? '');
const valuationSource = ref(props.policy?.details.valuation_source ?? '');

const isAllRisk = computed(() => subclass.value === 'All Risk');
</script>

<template>
    <Form
        v-bind="route"
        v-slot="{ errors, processing }"
        class="mx-auto flex w-full max-w-[1100px] flex-col gap-4"
    >
        <input type="hidden" name="class" value="automotive" />

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
                    />
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
