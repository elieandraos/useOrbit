<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import Button from '@/components/ui/button/Button.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import FormSection from '@/components/ui/form-section/FormSection.vue';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';
import Select from '@/components/ui/select/Select.vue';
import { Typeahead } from '@/components/ui/typeahead';
import type { TypeaheadOption } from '@/components/ui/typeahead';
import { index as policiesIndex } from '@/routes/policies';
import { create as policiesMedicalCreate } from '@/routes/policies/medical';

interface Option {
    label: string;
    value: string;
}

interface EntityOption {
    id: number;
    full_name?: string;
    name?: string;
}

const props = defineProps<{
    clients: EntityOption[];
    carriers: EntityOption[];
    agents: EntityOption[];
    classes: Option[];
    types: Option[];
    statuses: Option[];
    sources: Option[];
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

const policyClass = ref<string | null>(null);
const type = ref(props.types[0]?.value ?? 'single');
const clientId = ref<number | null>(null);
const carrierId = ref<number | null>(null);
const agentId = ref<number | null>(null);
const status = ref(props.statuses[0]?.value ?? 'active');
const source = ref('');

const canContinue = computed(
    () =>
        policyClass.value !== null &&
        clientId.value !== null &&
        carrierId.value !== null &&
        source.value !== '',
);

const continueHref = computed(() => {
    if (policyClass.value !== 'medical') {
        return null;
    }

    return policiesMedicalCreate.url({
        query: {
            type: type.value,
            client_id: clientId.value ?? '',
            carrier_id: carrierId.value ?? '',
            agent_id: agentId.value ?? '',
            status: status.value,
            source: source.value,
        },
    });
});
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
                title="Policy type"
                subtitle="Is this an individual or a group policy?"
            >
                <RadioChips v-model="type" :options="types" />
            </FormSection>

            <FormSection
                title="Insurance class"
                subtitle="Only Medical is available today; other classes are coming soon."
            >
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <button
                        v-for="policyClassOption in classes"
                        :key="policyClassOption.value"
                        type="button"
                        :disabled="policyClassOption.value !== 'medical'"
                        class="flex flex-col items-start gap-1 rounded-[10px] border p-4 text-left transition-colors disabled:cursor-not-allowed disabled:opacity-40"
                        :class="
                            policyClass === policyClassOption.value
                                ? 'border-accent bg-accent-bg'
                                : 'border-border bg-surface hover:border-accent/50'
                        "
                        @click="policyClass = policyClassOption.value"
                    >
                        <span class="text-sm font-medium text-primary">{{
                            policyClassOption.label
                        }}</span>
                    </button>
                </div>
            </FormSection>

            <FormSection
                title="Parties"
                subtitle="Who the policy belongs to and who's underwriting it."
            >
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <FormField label="Client" required>
                        <Typeahead
                            v-model="clientId"
                            :options="clientOptions"
                            placeholder="Select client"
                        />
                    </FormField>
                    <FormField label="Insurance company" required>
                        <Typeahead
                            v-model="carrierId"
                            :options="carrierOptions"
                            placeholder="Select carrier"
                        />
                    </FormField>
                    <FormField label="Agent" optional>
                        <Typeahead
                            v-model="agentId"
                            :options="agentOptions"
                            placeholder="Select agent"
                        />
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
                    <FormField label="Lead source" required>
                        <Select v-model="source" placeholder="Select">
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
                <Link v-if="continueHref" :href="continueHref">
                    <Button type="button" :disabled="!canContinue"
                        >Continue · Medical →</Button
                    >
                </Link>
                <Button v-else type="button" disabled>Continue</Button>
            </div>
        </div>
    </div>
</template>
