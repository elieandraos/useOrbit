<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { SearchIcon } from '@lucide/vue';
import { ref, watch } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import DateInput from '@/components/ui/date-input/DateInput.vue';
import Drawer from '@/components/ui/drawer/Drawer.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import RadioPills from '@/components/ui/radio-pills/RadioPills.vue';
import Select from '@/components/ui/select/Select.vue';
import { policyDateEndYear } from '@/lib/policyDateEndYear';
import { index as policiesIndex } from '@/routes/policies';
import type { PolicyCurrencyOption } from '@/types/policy';

interface Option {
    label: string;
    value: string;
}

interface CarrierOption {
    id: number;
    name: string;
}

interface Filters {
    search: string | null;
    status: string | null;
    type: string | null;
    class: string[] | null;
    carrier_id: string | number | null;
    source: string | null;
    currency_id: string | number | null;
    effective_from: string | null;
    effective_to: string | null;
    amount_min: string | number | null;
    amount_max: string | number | null;
}

const props = defineProps<{
    statuses: Option[];
    types: Option[];
    classes: Option[];
    sources: Option[];
    carriers: CarrierOption[];
    currencies: PolicyCurrencyOption[];
    filters: Filters;
}>();

const open = defineModel<boolean>('open', { default: false });

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const type = ref(props.filters.type ?? '');
const selectedClasses = ref<string[]>([...(props.filters.class ?? [])]);
const carrierId = ref(
    props.filters.carrier_id ? String(props.filters.carrier_id) : '',
);
const source = ref(props.filters.source ?? '');
const currencyId = ref(
    props.filters.currency_id ? String(props.filters.currency_id) : '',
);
const effectiveFrom = ref(props.filters.effective_from ?? '');
const effectiveTo = ref(props.filters.effective_to ?? '');
const amountMin = ref(props.filters.amount_min ?? '');
const amountMax = ref(props.filters.amount_max ?? '');
const formErrors = ref<Record<string, string>>({});

watch(open, (isOpen) => {
    formErrors.value = {};

    if (!isOpen) {
        return;
    }

    search.value = props.filters.search ?? '';
    status.value = props.filters.status ?? '';
    type.value = props.filters.type ?? '';
    selectedClasses.value = [...(props.filters.class ?? [])];
    carrierId.value = props.filters.carrier_id
        ? String(props.filters.carrier_id)
        : '';
    source.value = props.filters.source ?? '';
    currencyId.value = props.filters.currency_id
        ? String(props.filters.currency_id)
        : '';
    effectiveFrom.value = props.filters.effective_from ?? '';
    effectiveTo.value = props.filters.effective_to ?? '';
    amountMin.value = props.filters.amount_min ?? '';
    amountMax.value = props.filters.amount_max ?? '';
});

// Amounts are only comparable within one currency, so clearing it clears the range.
watch(currencyId, (selectedCurrencyId) => {
    if (selectedCurrencyId) {
        return;
    }

    amountMin.value = '';
    amountMax.value = '';
});

function isClassSelected(value: string): boolean {
    return selectedClasses.value.includes(value);
}

function toggleClass(value: string, checked: boolean) {
    if (checked) {
        if (!selectedClasses.value.includes(value)) {
            selectedClasses.value = [...selectedClasses.value, value];
        }

        return;
    }

    selectedClasses.value = selectedClasses.value.filter(
        (selected) => selected !== value,
    );
}

function applyFilters() {
    const query: Record<string, string | number | string[]> = {};

    if (search.value) {
        query.search = search.value;
    }

    if (status.value) {
        query.status = status.value;
    }

    if (type.value) {
        query.type = type.value;
    }

    if (selectedClasses.value.length > 0) {
        query.class = selectedClasses.value;
    }

    if (carrierId.value) {
        query.carrier_id = carrierId.value;
    }

    if (source.value) {
        query.source = source.value;
    }

    if (currencyId.value) {
        query.currency_id = currencyId.value;
    }

    if (effectiveFrom.value) {
        query.effective_from = effectiveFrom.value;
    }

    if (effectiveTo.value) {
        query.effective_to = effectiveTo.value;
    }

    if (amountMin.value !== '' && amountMin.value !== null) {
        query.amount_min = amountMin.value;
    }

    if (amountMax.value !== '' && amountMax.value !== null) {
        query.amount_max = amountMax.value;
    }

    formErrors.value = {};
    router.get(
        policiesIndex.url({ query }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                open.value = false;
            },
            onError: (errors) => {
                formErrors.value = errors as Record<string, string>;
            },
        },
    );
}

function clearFilters() {
    open.value = false;
    router.get(policiesIndex.url());
}
</script>

<template>
    <Drawer v-model:open="open" title="Policies Filters">
        <div class="flex flex-col gap-5">
            <FormField
                label="Search"
                for="filter_search"
                :error="formErrors.search"
            >
                <Input
                    id="filter_search"
                    v-model="search"
                    placeholder="Policy number or subclass"
                >
                    <template #leading>
                        <SearchIcon />
                    </template>
                </Input>
            </FormField>

            <FormField label="Status" :error="formErrors.status">
                <RadioPills v-model="status" :options="statuses" size="sm" />
            </FormField>

            <FormField label="Type" :error="formErrors.type">
                <RadioPills v-model="type" :options="types" size="sm" />
            </FormField>

            <FormField
                label="Class"
                :error="formErrors['class.0'] ?? formErrors.class"
            >
                <div class="flex flex-col gap-2.5">
                    <Label
                        v-for="option in classes"
                        :key="option.value"
                        class="gap-2.5 font-normal"
                    >
                        <Checkbox
                            :model-value="isClassSelected(option.value)"
                            @update:model-value="
                                (checked) => toggleClass(option.value, checked)
                            "
                        />
                        <span>{{ option.label }}</span>
                    </Label>
                </div>
            </FormField>

            <FormField
                label="Company"
                for="filter_carrier_id"
                :error="formErrors.carrier_id"
            >
                <Select id="filter_carrier_id" v-model="carrierId" size="sm">
                    <option value="">All companies</option>
                    <option
                        v-for="carrier in carriers"
                        :key="carrier.id"
                        :value="`${carrier.id}`"
                    >
                        {{ carrier.name }}
                    </option>
                </Select>
            </FormField>

            <FormField label="Source" :error="formErrors.source">
                <RadioPills v-model="source" :options="sources" size="sm" />
            </FormField>

            <FormField
                label="Effective date"
                :error="formErrors.effective_to ?? formErrors.effective_from"
            >
                <div class="flex flex-col gap-3">
                    <div class="flex flex-col gap-1.5">
                        <span class="text-xs text-tertiary">From</span>
                        <DateInput
                            v-model="effectiveFrom"
                            size="sm"
                            :end-year="policyDateEndYear"
                        />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <span class="text-xs text-tertiary">To</span>
                        <DateInput
                            v-model="effectiveTo"
                            size="sm"
                            :end-year="policyDateEndYear"
                        />
                    </div>
                </div>
            </FormField>

            <FormField
                label="Currency"
                for="filter_currency_id"
                :error="formErrors.currency_id"
            >
                <Select id="filter_currency_id" v-model="currencyId" size="sm">
                    <option value="">All currencies</option>
                    <option
                        v-for="currency in currencies"
                        :key="currency.id"
                        :value="`${currency.id}`"
                    >
                        {{ currency.code }} — {{ currency.name }}
                    </option>
                </Select>
            </FormField>

            <FormField
                label="Amount range"
                :error="formErrors.amount_max ?? formErrors.amount_min"
                :helper="
                    currencyId
                        ? undefined
                        : 'Select a currency to filter by amount.'
                "
            >
                <div class="flex items-center gap-3">
                    <Input
                        v-model="amountMin"
                        type="number"
                        min="0"
                        step="0.01"
                        size="sm"
                        placeholder="Min"
                        :disabled="!currencyId"
                    />
                    <span class="text-xs text-tertiary">to</span>
                    <Input
                        v-model="amountMax"
                        type="number"
                        min="0"
                        step="0.01"
                        size="sm"
                        placeholder="Max"
                        :disabled="!currencyId"
                    />
                </div>
            </FormField>
        </div>

        <template #footer>
            <Button variant="ghost" size="md" @click="clearFilters"
                >Clear filters</Button
            >
            <div class="flex-1" />
            <Button variant="primary" size="md" @click="applyFilters"
                >Apply filters</Button
            >
        </template>
    </Drawer>
</template>
