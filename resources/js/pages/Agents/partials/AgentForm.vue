<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { Mail, MapPin, Phone } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import DateInput from '@/components/ui/date-input/DateInput.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import FormSection from '@/components/ui/form-section/FormSection.vue';
import Input from '@/components/ui/input/Input.vue';
import { Typeahead } from '@/components/ui/typeahead';
import type { TypeaheadOption } from '@/components/ui/typeahead';
import { useStateOptions } from '@/composables/useWorldLocations';
import { index as agentsIndex } from '@/routes/agents';
import type { RouteFormDefinition } from '@/wayfinder';
import type { AgentResource } from './agent';

const props = defineProps<{
    countries: { id: number; name: string }[];
    agent?: AgentResource;
    route: RouteFormDefinition<'post'>;
    submitLabel: string;
}>();

const defaultCountryId = computed(
    () =>
        props.countries.find((country) => country.name === 'Lebanon')?.id ??
        null,
);

const firstName = ref(props.agent?.first_name ?? '');
const lastName = ref(props.agent?.last_name ?? '');
const dateOfBirth = ref(props.agent?.date_of_birth ?? '');
const phone = ref(props.agent?.phone ?? '');
const email = ref(props.agent?.email ?? '');
const street = ref(props.agent?.street ?? '');
const buildingFloor = ref(props.agent?.building_floor ?? '');
const countryId = ref<number | null>(
    props.agent?.country_id ?? defaultCountryId.value,
);
const stateId = ref<number | null>(props.agent?.state_id ?? null);
const city = ref(props.agent?.city ?? '');

const countryOptions = computed<TypeaheadOption[]>(() =>
    props.countries.map((country) => ({
        value: country.id,
        label: country.name,
    })),
);
const { options: stateOptions, loading: stateLoading } =
    useStateOptions(countryId);

watch(countryId, () => {
    stateId.value = null;
});
</script>

<template>
    <Form
        v-bind="route"
        v-slot="{ errors, processing }"
        class="mx-auto flex w-full max-w-[1100px] flex-col gap-4"
    >
        <FormSection
            title="Personal information"
            subtitle="The agent's legal name and date of birth."
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <FormField
                    label="First name"
                    for="first_name"
                    required
                    :error="errors.first_name"
                >
                    <Input
                        id="first_name"
                        v-model="firstName"
                        name="first_name"
                    />
                </FormField>
                <FormField
                    label="Last name"
                    for="last_name"
                    required
                    :error="errors.last_name"
                >
                    <Input id="last_name" v-model="lastName" name="last_name" />
                </FormField>
                <FormField
                    label="Date of birth"
                    required
                    :error="errors.date_of_birth"
                >
                    <DateInput v-model="dateOfBirth" name="date_of_birth" />
                </FormField>
            </div>
        </FormSection>

        <FormSection
            title="Contact"
            subtitle="How you and clients can reach this agent."
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Phone number"
                    for="phone"
                    required
                    :error="errors.phone"
                >
                    <Input id="phone" v-model="phone" name="phone" type="tel">
                        <template #leading>
                            <Phone />
                        </template>
                    </Input>
                </FormField>
                <FormField
                    label="Email"
                    for="email"
                    required
                    :error="errors.email"
                >
                    <Input id="email" v-model="email" name="email" type="email">
                        <template #leading>
                            <Mail />
                        </template>
                    </Input>
                </FormField>
            </div>
        </FormSection>

        <FormSection title="Address" subtitle="Where this agent is based.">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Street"
                    for="street"
                    optional
                    :error="errors.street"
                >
                    <Input id="street" v-model="street" name="street">
                        <template #leading>
                            <MapPin />
                        </template>
                    </Input>
                </FormField>
                <FormField
                    label="Building / Floor"
                    for="building_floor"
                    optional
                    :error="errors.building_floor"
                >
                    <Input
                        id="building_floor"
                        v-model="buildingFloor"
                        name="building_floor"
                    />
                </FormField>
                <FormField
                    label="City"
                    for="city"
                    optional
                    :error="errors.city"
                >
                    <Input id="city" v-model="city" name="city" />
                </FormField>
                <FormField
                    label="Governorate"
                    for="state_id"
                    optional
                    :error="errors.state_id"
                >
                    <Typeahead
                        id="state_id"
                        v-model="stateId"
                        name="state_id"
                        :options="stateOptions"
                        :loading="stateLoading"
                        :initial-label="agent?.state_name"
                        placeholder="Select"
                    />
                </FormField>
                <FormField
                    label="Country"
                    for="country_id"
                    optional
                    :error="errors.country_id"
                >
                    <Typeahead
                        id="country_id"
                        v-model="countryId"
                        name="country_id"
                        :options="countryOptions"
                        placeholder="Select"
                    />
                </FormField>
            </div>
        </FormSection>

        <div class="flex justify-end gap-3">
            <Link :href="agentsIndex().url">
                <Button type="button" variant="ghost">Cancel</Button>
            </Link>
            <Button type="submit" :disabled="processing">{{
                submitLabel
            }}</Button>
        </div>
    </Form>
</template>
