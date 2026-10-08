<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import { DetailField } from '@/components/ui/detail-field';
import FormSection from '@/components/ui/form-section/FormSection.vue';
import { carryPolicyWork } from '@/lib/policyCreateFlow';
import type { PolicyCarriedWork } from '@/lib/policyCreateFlow';
import { create as policiesCreate } from '@/routes/policies';
import type { PolicyEntry } from '@/types/policy';

const props = defineProps<{
    entry: PolicyEntry;
    errors: Record<string, string | undefined>;
    /** This step's entries, carried back to the first step by Back (also the way to correct the errors shown here). */
    carriedWork: () => PolicyCarriedWork;
}>();

const carryBack = carryPolicyWork(() => props.carriedWork());

/**
 * The first step, with this summary's choices selected again.
 */
const entryHref = computed(() =>
    policiesCreate.url({
        query: {
            class: props.entry.class.value,
            type: props.entry.type.value,
            client_id: props.entry.client.id,
            carrier_id: props.entry.carrier.id,
            agent_id: props.entry.agent?.id,
            source: props.entry.source.value,
        },
    }),
);

/**
 * Errors for the first-step fields, which this step only submits as hidden inputs.
 */
const entryErrors = computed(() =>
    ['class', 'type', 'client_id', 'carrier_id', 'agent_id', 'source']
        .map((key) => props.errors[key])
        .filter((error): error is string => error !== undefined),
);
</script>

<template>
    <FormSection
        title="Policy"
        subtitle="Chosen on the first step. Go back to change any of these."
    >
        <input type="hidden" name="type" :value="entry.type.value" />
        <input type="hidden" name="client_id" :value="entry.client.id" />
        <input type="hidden" name="carrier_id" :value="entry.carrier.id" />
        <input type="hidden" name="agent_id" :value="entry.agent?.id ?? ''" />
        <input type="hidden" name="source" :value="entry.source.value" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
            <div
                class="grid flex-1 grid-cols-2 gap-x-3.5 gap-y-4 sm:grid-cols-3"
            >
                <DetailField
                    label="Insurance class"
                    :value="entry.class.label"
                />
                <DetailField label="Policy type" :value="entry.type.label" />
                <DetailField label="Lead source" :value="entry.source.label" />
                <DetailField label="Client" :value="entry.client.full_name" />
                <DetailField
                    label="Insurance company"
                    :value="entry.carrier.name"
                />
                <DetailField label="Agent" :value="entry.agent?.full_name" />
            </div>
            <Link :href="entryHref" v-bind="carryBack">
                <Button type="button" variant="ghost">
                    <template #leading><ArrowLeft /></template>
                    Back
                </Button>
            </Link>
        </div>

        <div v-if="entryErrors.length" class="flex flex-col gap-1">
            <p
                v-for="error in entryErrors"
                :key="error"
                class="text-xs text-danger"
            >
                {{ error }}
            </p>
        </div>
    </FormSection>
</template>
