<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { SearchIcon } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import DateInput from '@/components/ui/date-input/DateInput.vue';
import Drawer from '@/components/ui/drawer/Drawer.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';
import { Typeahead } from '@/components/ui/typeahead';
import type { TypeaheadOption } from '@/components/ui/typeahead';
import { index as policiesIndex } from '@/routes/policies';

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
    filters: Filters;
}>();

const open = defineModel<boolean>('open', { default: false });

const carrierOptions = computed<TypeaheadOption[]>(() =>
    props.carriers.map((carrier) => ({
        value: carrier.id,
        label: carrier.name,
    })),
);

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const type = ref(props.filters.type ?? '');
const selectedClasses = ref<string[]>([...(props.filters.class ?? [])]);
const carrierId = ref<number | null>(
    props.filters.carrier_id ? Number(props.filters.carrier_id) : null,
);
const source = ref(props.filters.source ?? '');
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
        ? Number(props.filters.carrier_id)
        : null;
    source.value = props.filters.source ?? '';
    effectiveFrom.value = props.filters.effective_from ?? '';
    effectiveTo.value = props.filters.effective_to ?? '';
    amountMin.value = props.filters.amount_min ?? '';
    amountMax.value = props.filters.amount_max ?? '';
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

    if (carrierId.value !== null) {
        query.carrier_id = carrierId.value;
    }

    if (source.value) {
        query.source = source.value;
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
                <RadioChips v-model="status" :options="statuses" />
            </FormField>

            <FormField label="Type" :error="formErrors.type">
                <RadioChips v-model="type" :options="types" />
            </FormField>

            <FormField
                label="Class"
                :error="formErrors['class.0'] ?? formErrors.class"
            >
                <div class="flex flex-col gap-2.5">
                    <label
                        v-for="option in classes"
                        :key="option.value"
                        class="flex items-center gap-2.5"
                    >
                        <Checkbox
                            :model-value="isClassSelected(option.value)"
                            @update:model-value="
                                (checked) => toggleClass(option.value, checked)
                            "
                        />
                        <Label class="font-normal">{{ option.label }}</Label>
                    </label>
                </div>
            </FormField>

            <FormField label="Company" :error="formErrors.carrier_id">
                <Typeahead
                    v-model="carrierId"
                    :options="carrierOptions"
                    placeholder="Select carrier"
                />
            </FormField>

            <FormField label="Source" :error="formErrors.source">
                <RadioChips v-model="source" :options="sources" />
            </FormField>

            <FormField
                label="Effective date"
                :error="formErrors.effective_to ?? formErrors.effective_from"
            >
                <div class="flex flex-col gap-3">
                    <div class="flex flex-col gap-1.5">
                        <span class="text-xs text-tertiary">From</span>
                        <DateInput v-model="effectiveFrom" size="sm" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <span class="text-xs text-tertiary">To</span>
                        <DateInput v-model="effectiveTo" size="sm" />
                    </div>
                </div>
            </FormField>

            <FormField
                label="Amount range"
                :error="formErrors.amount_max ?? formErrors.amount_min"
            >
                <div class="flex items-center gap-3">
                    <Input
                        v-model="amountMin"
                        type="number"
                        min="0"
                        placeholder="Min"
                    />
                    <span class="text-xs text-tertiary">to</span>
                    <Input
                        v-model="amountMax"
                        type="number"
                        min="0"
                        placeholder="Max"
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
