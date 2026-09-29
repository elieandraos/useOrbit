<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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

const policyClass = ref(props.selected.class ?? '');
const type = ref(props.selected.type ?? props.types[0]?.value ?? 'single');
const clientId = ref(`${props.selected.client_id ?? ''}`);
const carrierId = ref(`${props.selected.carrier_id ?? ''}`);
const agentId = ref(`${props.selected.agent_id ?? ''}`);
const status = ref(
    props.selected.status ?? props.statuses[0]?.value ?? 'active',
);
const source = ref(props.selected.source ?? '');

const showErrors = ref(false);

const errors = computed<Record<string, string | undefined>>(() => {
    if (!showErrors.value) {
        return {};
    }

    return {
        class: policyClass.value ? undefined : 'Choose an insurance class.',
        client_id: clientId.value ? undefined : 'Select a client.',
        carrier_id: carrierId.value
            ? undefined
            : 'Select an insurance company.',
        source: source.value ? undefined : 'Select a lead source.',
    };
});

const selectedClassLabel = computed(
    () =>
        props.classes.find((option) => option.value === policyClass.value)
            ?.label,
);

function continueToClass(): void {
    showErrors.value = true;

    const createRoute = classCreateRoutes[policyClass.value];

    if (
        !createRoute ||
        Object.values(errors.value).some((error) => error !== undefined)
    ) {
        return;
    }

    router.visit(
        createRoute.url({
            query: {
                type: type.value,
                client_id: clientId.value,
                carrier_id: carrierId.value,
                agent_id: agentId.value,
                status: status.value,
                source: source.value,
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
                <FormField label="Policy type" required>
                    <RadioChips v-model="type" :options="types" />
                </FormField>
                <FormField
                    label="Insurance class"
                    required
                    :error="errors.class"
                >
                    <RadioChips v-model="policyClass" :options="classes" />
                </FormField>
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
                            v-model="clientId"
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
                            v-model="carrierId"
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
                        <Select id="agent_id" v-model="agentId">
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
                        <RadioChips v-model="status" :options="statuses" />
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
