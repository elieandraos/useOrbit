<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import Button from '@/components/ui/button/Button.vue';
import { create as policiesCreate } from '@/routes/policies';

const props = defineProps<{
    policyClass: string;
    /** The id of the class form whose current shared values carry back to the entry screen. */
    form: string;
}>();

const firstStepKeys = [
    'type',
    'client_id',
    'carrier_id',
    'agent_id',
    'status',
    'source',
];

function backToEntry(): void {
    const form = document.getElementById(props.form);
    const values = form instanceof HTMLFormElement ? new FormData(form) : null;
    const selection: Record<string, string> = { class: props.policyClass };

    for (const key of firstStepKeys) {
        const value = values?.get(key);

        if (typeof value === 'string' && value) {
            selection[key] = value;
        }
    }

    router.visit(policiesCreate.url({ query: selection }));
}
</script>

<template>
    <Button type="button" variant="ghost" @click="backToEntry">
        <template #leading><ArrowLeft /></template>
        Back
    </Button>
</template>
