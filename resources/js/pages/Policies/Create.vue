<script setup lang="ts">
import { Head, Link, router, useRemember } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import Button from '@/components/ui/button/Button.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import FormSection from '@/components/ui/form-section/FormSection.vue';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';
import Select from '@/components/ui/select/Select.vue';
import {
    applyPolicyDiscardRules,
    carryPolicyWork,
    checkPolicyCreateSnapshot,
    clonePolicyEntries,
    dropDiscardedPolicyWork,
    endPolicyCreateFlow,
    stampPolicyCreateFlow,
    startPolicyCreateFlow,
    takePolicyCarriedWork,
} from '@/lib/policyCreateFlow';
import type {
    PolicyCarriedWork,
    PolicyCreateFlowStamp,
} from '@/lib/policyCreateFlow';
import PolicyClientTypeahead from '@/pages/Policies/partials/PolicyClientTypeahead.vue';
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
    source: string | null;
    /** The selected client with its displayed name, resolved by the server. */
    client: { id: number; full_name: string } | null;
}

interface RememberedSelection extends PolicyCreateFlowStamp {
    class: string;
    type: string;
    client_id: number | string | null;
    /** The selected client's displayed name, remembered so Back/Forward keeps it without a lookup. */
    client_label: string | null;
    carrier_id: string;
    agent_id: string;
    source: string;
    /** The second step's entries, received from it and handed on at Continue. */
    work: PolicyCarriedWork | null;
}

const props = defineProps<{
    carriers: PolicyPartyOption[];
    agents: PolicyPartyOption[];
    classes: Option[];
    types: Option[];
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

const rememberKey = 'Policies/Create';
const restored = router.restore(rememberKey) as RememberedSelection | undefined;
const handedOver = takePolicyCarriedWork();
const restoreCheck = restored ? checkPolicyCreateSnapshot(restored) : null;

let initialSelection: RememberedSelection;

if (restored && restoreCheck) {
    const snapshot = clonePolicyEntries(restored);

    initialSelection = {
        ...snapshot,
        ...stampPolicyCreateFlow(),
        work: snapshot.work
            ? dropDiscardedPolicyWork(snapshot.work, restoreCheck)
            : null,
    };
} else {
    // A visit that isn't a flow navigation starts a new flow. An older entry of another flow (or of this one
    // after a refresh) restored from history starts empty, joining the current flow rather than replacing it.
    if (!handedOver && !restored) {
        startPolicyCreateFlow();
    }

    initialSelection = {
        class: props.selected.class ?? '',
        type: props.selected.type ?? props.types[0]?.value ?? 'single',
        client_id: props.selected.client?.id ?? null,
        client_label: props.selected.client?.full_name ?? null,
        carrier_id: `${props.selected.carrier_id ?? ''}`,
        agent_id: `${props.selected.agent_id ?? ''}`,
        source: props.selected.source ?? '',
        work: handedOver?.work ?? null,
        ...stampPolicyCreateFlow(),
    };
}

// Written back first, so the restore below gets the checked, re-stamped snapshot instead of the stored one.
router.remember(clonePolicyEntries(initialSelection), rememberKey);

/**
 * Remembered in the browser history entry, so browser Back to this screen restores the choices made here, and the
 * second step's work it carries.
 */
const selection = useRemember(
    reactive(initialSelection),
    rememberKey,
) as RememberedSelection;

/**
 * A different class or carrier discards the carried class entries or issuing branch as soon as it's chosen.
 */
watch(
    () => [selection.class, selection.carrier_id] as const,
    ([policyClass, carrierId]) => {
        if (!selection.work) {
            return;
        }

        selection.work = applyPolicyDiscardRules(
            selection.work,
            policyClass,
            carrierId,
        );
        Object.assign(selection, stampPolicyCreateFlow());
    },
);

const carryToClass = carryPolicyWork(() => selection.work);

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
                client_id: selection.client_id ?? '',
                carrier_id: selection.carrier_id,
                agent_id: selection.agent_id,
                source: selection.source,
            },
        }),
        carryToClass,
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
                        <PolicyClientTypeahead
                            id="client_id"
                            v-model="selection.client_id"
                            v-model:label="selection.client_label"
                        />
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

            <FormSection title="Origin" subtitle="How this policy came to you.">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                <Link :href="policiesIndex().url" @before="endPolicyCreateFlow">
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
