<script setup lang="ts">
import { Head, Link, router, useRemember } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import Button from '@/components/ui/button/Button.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import FormSection from '@/components/ui/form-section/FormSection.vue';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';
import Select from '@/components/ui/select/Select.vue';
import { index as policiesIndex } from '@/routes/policies';
import { create as policiesAutomotiveCreate } from '@/routes/policies/automotive';
import { create as policiesExpatCreate } from '@/routes/policies/expat';
import { create as policiesFireCreate } from '@/routes/policies/fire';
import { create as policiesLifeCreate } from '@/routes/policies/life';
import { create as policiesMedicalCreate } from '@/routes/policies/medical';
import { create as policiesTravelCreate } from '@/routes/policies/travel';
import type { PolicyPartyOption } from '@/types/policy';

interface Option {
    label: string;
    value: string;
}

interface FirstStepSelection {
    class: string | null;
    type: string | null;
    client_id: number | null;
    carrier_id: number | null;
    agent_id: number | null;
    status: string | null;
    source: string | null;
}

const props = defineProps<{
    clients: PolicyPartyOption[];
    carriers: PolicyPartyOption[];
    agents: PolicyPartyOption[];
    classes: Option[];
    types: Option[];
    statuses: Option[];
    sources: Option[];
    selected: FirstStepSelection;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Policies',
                href: policiesIndex(),
            },
            {
                title: 'New policy',
            },
        ],
    },
});

const classCreateRoutes: Record<string, typeof policiesMedicalCreate> = {
    medical: policiesMedicalCreate,
    automotive: policiesAutomotiveCreate,
    expat: policiesExpatCreate,
    fire: policiesFireCreate,
    life: policiesLifeCreate,
    travel: policiesTravelCreate,
};

/**
 * Remembered in the browser history entry, so browser Back to this screen restores the choices made here.
 */
const selection = useRemember(
    reactive({
        class: props.selected.class ?? '',
        type: props.selected.type ?? props.types[0]?.value ?? 'single',
        client_id: `${props.selected.client_id ?? ''}`,
        carrier_id: `${props.selected.carrier_id ?? ''}`,
        agent_id: `${props.selected.agent_id ?? ''}`,
        status: props.selected.status ?? props.statuses[0]?.value ?? 'active',
        source: props.selected.source ?? '',
    }),
    'Policies/Create',
) as Record<keyof FirstStepSelection, string>;

const showErrors = ref(false);

const errors = computed<Record<string, string | undefined>>(() => {
    if (!showErrors.value) {
        return {};
    }

    return {
        class: selection.class ? undefined : 'Choose an insurance class.',
        client_id: selection.client_id ? undefined : 'Select a client.',
        carrier_id: selection.carrier_id
            ? undefined
            : 'Select an insurance company.',
        source: selection.source ? undefined : 'Select a lead source.',
    };
});

const selectedClassLabel = computed(
    () =>
        props.classes.find((option) => option.value === selection.class)?.label,
);

function continueToClass(): void {
    showErrors.value = true;

    const createRoute = classCreateRoutes[selection.class];

    if (
        !createRoute ||
        Object.values(errors.value).some((error) => error !== undefined)
    ) {
        return;
    }

    router.visit(
        createRoute.url({
            query: {
                type: selection.type,
                client_id: selection.client_id,
                carrier_id: selection.carrier_id,
                agent_id: selection.agent_id,
                status: selection.status,
                source: selection.source,
            },
        }),
    );
}
</script>

<template>
    <Head title="New policy" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="New policy"
            subtitle="Choose a policy class to continue — the rest of the form depends on it."
            :divider="false"
        />

        <div class="mx-auto flex w-full max-w-[1100px] flex-col gap-4">
            <FormSection
                title="Policy type & class"
                subtitle="Is this an individual or a group policy, and what does it insure?"
            >
                <div class="flex flex-wrap gap-x-10 gap-y-6">
                    <FormField label="Policy type" required>
                        <RadioChips v-model="selection.type" :options="types" />
                    </FormField>
                    <FormField
                        label="Insurance class"
                        required
                        :error="errors.class"
                    >
                        <RadioChips
                            v-model="selection.class"
                            :options="classes"
                        />
                    </FormField>
                </div>
            </FormSection>

            <FormSection
                title="Parties"
                subtitle="Who the policy belongs to and who's underwriting it."
            >
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <FormField
                        label="Client"
                        for="client_id"
                        required
                        :error="errors.client_id"
                    >
                        <Select
                            id="client_id"
                            v-model="selection.client_id"
                            placeholder="Select client"
                        >
                            <option
                                v-for="client in clients"
                                :key="client.id"
                                :value="`${client.id}`"
                            >
                                {{ client.full_name }}
                            </option>
                        </Select>
                    </FormField>
                    <FormField
                        label="Insurance company"
                        for="carrier_id"
                        required
                        :error="errors.carrier_id"
                    >
                        <Select
                            id="carrier_id"
                            v-model="selection.carrier_id"
                            placeholder="Select carrier"
                        >
                            <option
                                v-for="carrier in carriers"
                                :key="carrier.id"
                                :value="`${carrier.id}`"
                            >
                                {{ carrier.name }}
                            </option>
                        </Select>
                    </FormField>
                    <FormField label="Agent" for="agent_id" optional>
                        <Select id="agent_id" v-model="selection.agent_id">
                            <option value="">No agent</option>
                            <option
                                v-for="agent in agents"
                                :key="agent.id"
                                :value="`${agent.id}`"
                            >
                                {{ agent.full_name }}
                            </option>
                        </Select>
                    </FormField>
                </div>
            </FormSection>

            <FormSection
                title="Status & origin"
                subtitle="Current status and how this policy came to you."
            >
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField label="Status" required>
                        <RadioChips
                            v-model="selection.status"
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
                            v-model="selection.source"
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
                </div>
            </FormSection>

            <div class="flex justify-end gap-3">
                <Link :href="policiesIndex().url">
                    <Button type="button" variant="ghost">Cancel</Button>
                </Link>
                <Button type="button" @click="continueToClass">
                    {{
                        selectedClassLabel
                            ? `Continue · ${selectedClassLabel} →`
                            : 'Continue'
                    }}
                </Button>
            </div>
        </div>
    </div>
</template>
