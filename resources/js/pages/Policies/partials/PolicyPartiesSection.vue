<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import FormSection from '@/components/ui/form-section/FormSection.vue';
import Select from '@/components/ui/select/Select.vue';
import type { PolicyParties, PolicyPartyOption } from '@/types/policy';

const props = defineProps<{
    clients: PolicyPartyOption[];
    carriers: PolicyPartyOption[];
    agents: PolicyPartyOption[];
    policy?: PolicyParties;
    defaults?: Record<string, string>;
    errors: Record<string, string | undefined>;
}>();

const clientId = ref(
    props.policy
        ? `${props.policy.client.id}`
        : (props.defaults?.client_id ?? ''),
);
const carrierId = ref(
    props.policy
        ? `${props.policy.carrier.id}`
        : (props.defaults?.carrier_id ?? ''),
);
const carrierBranchId = ref(
    props.policy ? `${props.policy.carrier_branch?.id ?? ''}` : '',
);
const carrierBranches = computed(
    () =>
        props.carriers.find((carrier) => `${carrier.id}` === carrierId.value)
            ?.branches ?? [],
);

watch(carrierId, () => {
    carrierBranchId.value = '';
});

const agentId = ref(
    props.policy
        ? `${props.policy.agent?.id ?? ''}`
        : (props.defaults?.agent_id ?? ''),
);
</script>

<template>
    <FormSection
        title="Parties"
        subtitle="Who the policy belongs to and who's underwriting it."
    >
        <FormField
            label="Client"
            for="client_id"
            required
            :error="errors.client_id"
        >
            <Select
                id="client_id"
                v-model="clientId"
                name="client_id"
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
                name="carrier_id"
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
        <FormField
            label="Issuing branch"
            for="carrier_branch_id"
            optional
            :error="errors.carrier_branch_id"
        >
            <Select
                id="carrier_branch_id"
                v-model="carrierBranchId"
                name="carrier_branch_id"
            >
                <option value="">No branch</option>
                <option
                    v-for="branch in carrierBranches"
                    :key="branch.id"
                    :value="`${branch.id}`"
                >
                    {{ branch.label }}
                </option>
            </Select>
        </FormField>
        <FormField
            label="Agent"
            for="agent_id"
            optional
            :error="errors.agent_id"
        >
            <Select id="agent_id" v-model="agentId" name="agent_id">
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
    </FormSection>
</template>
