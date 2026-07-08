<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { SearchIcon } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import DateInput from '@/components/ui/date-input/DateInput.vue';
import Drawer from '@/components/ui/drawer/Drawer.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import Input from '@/components/ui/input/Input.vue';
import RadioPills from '@/components/ui/radio-pills/RadioPills.vue';
import RangeSlider from '@/components/ui/range-slider/RangeSlider.vue';
import { index as clientsIndex } from '@/routes/clients';

const AGE_MIN_BOUND = 18;
const AGE_MAX_BOUND = 90;

interface Filters {
    search: string | null;
    gender: string | null;
    enrolled_from: string | null;
    enrolled_to: string | null;
    age_min: string | number | null;
    age_max: string | number | null;
}

const props = defineProps<{
    genders: { label: string; value: string }[];
    filters: Filters;
}>();

const open = defineModel<boolean>('open', { default: false });

const search = ref(props.filters.search ?? '');
const gender = ref(props.filters.gender ?? '');
const enrolledFrom = ref(props.filters.enrolled_from ?? '');
const enrolledTo = ref(props.filters.enrolled_to ?? '');
const ageRange = ref<[number, number]>([
    props.filters.age_min ? Number(props.filters.age_min) : AGE_MIN_BOUND,
    props.filters.age_max ? Number(props.filters.age_max) : AGE_MAX_BOUND,
]);

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    search.value = props.filters.search ?? '';
    gender.value = props.filters.gender ?? '';
    enrolledFrom.value = props.filters.enrolled_from ?? '';
    enrolledTo.value = props.filters.enrolled_to ?? '';
    ageRange.value = [
        props.filters.age_min ? Number(props.filters.age_min) : AGE_MIN_BOUND,
        props.filters.age_max ? Number(props.filters.age_max) : AGE_MAX_BOUND,
    ];
});

const genderOptions = computed(() => [
    { label: 'Any', value: '' },
    ...props.genders,
]);

const dateRangeError = computed(() => {
    if (
        enrolledFrom.value &&
        enrolledTo.value &&
        enrolledFrom.value > enrolledTo.value
    ) {
        return 'The "From" date must be before the "To" date.';
    }

    return undefined;
});

function applyFilters() {
    if (dateRangeError.value) {
        return;
    }

    const query: Record<string, string | number> = {};

    if (search.value) {
        query.search = search.value;
    }

    if (gender.value) {
        query.gender = gender.value;
    }

    if (enrolledFrom.value) {
        query.enrolled_from = enrolledFrom.value;
    }

    if (enrolledTo.value) {
        query.enrolled_to = enrolledTo.value;
    }

    if (ageRange.value[0] > AGE_MIN_BOUND) {
        query.age_min = ageRange.value[0];
    }

    if (ageRange.value[1] < AGE_MAX_BOUND) {
        query.age_max = ageRange.value[1];
    }

    open.value = false;
    router.get(clientsIndex.url({ query }));
}

function clearFilters() {
    open.value = false;
    router.get(clientsIndex.url());
}
</script>

<template>
    <Drawer
        v-model:open="open"
        title="Filters"
        description="Narrow down the client list."
    >
        <div class="flex flex-col gap-5">
            <FormField label="Search" for="filter_search">
                <Input
                    id="filter_search"
                    v-model="search"
                    placeholder="Name, phone, or email"
                >
                    <template #leading>
                        <SearchIcon />
                    </template>
                </Input>
            </FormField>

            <FormField label="Gender">
                <RadioPills v-model="gender" :options="genderOptions" />
            </FormField>

            <FormField label="Enrollment date" :error="dateRangeError">
                <div class="flex flex-col gap-3">
                    <div class="flex flex-col gap-1.5">
                        <span class="text-xs text-tertiary">From</span>
                        <DateInput v-model="enrolledFrom" size="sm" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <span class="text-xs text-tertiary">To</span>
                        <DateInput v-model="enrolledTo" size="sm" />
                    </div>
                </div>
            </FormField>

            <FormField label="Age range">
                <p class="mb-1 text-sm text-secondary">
                    {{ ageRange[0] }} – {{ ageRange[1] }} yrs
                </p>
                <RangeSlider
                    v-model="ageRange"
                    :min="AGE_MIN_BOUND"
                    :max="AGE_MAX_BOUND"
                />
            </FormField>
        </div>

        <template #footer>
            <Button variant="ghost" size="md" @click="clearFilters"
                >Clear filters</Button
            >
            <div class="flex-1" />
            <Button
                variant="primary"
                size="md"
                :disabled="!!dateRangeError"
                @click="applyFilters"
                >Apply filters</Button
            >
        </template>
    </Drawer>
</template>
