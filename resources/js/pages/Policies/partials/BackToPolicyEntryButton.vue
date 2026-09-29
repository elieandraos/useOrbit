<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import Button from '@/components/ui/button/Button.vue';
import { create as policiesCreate } from '@/routes/policies';

const props = defineProps<{
    policyClass: string;
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
    const query = new URLSearchParams(window.location.search);
    const selection: Record<string, string> = { class: props.policyClass };

    for (const key of firstStepKeys) {
        const value = query.get(key);

        if (value) {
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
