<script setup lang="ts">
import Badge from '@/components/ui/badge/Badge.vue';
import Label from '@/components/ui/label/Label.vue';

interface Props {
    label: string;
    for?: string;
    required?: boolean;
    optional?: boolean;
    helper?: string;
    error?: string;
    success?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <div class="flex items-center gap-2">
            <Label :for="props.for">{{ label }}</Label>
            <Badge v-if="required" tone="danger">Required</Badge>
            <Badge v-if="optional" tone="neutral">Optional</Badge>
        </div>

        <slot />

        <p v-if="error" class="text-xs text-danger">{{ error }}</p>
        <p v-else-if="success" class="text-xs text-success">{{ success }}</p>
        <p v-else-if="helper" class="text-xs text-tertiary">{{ helper }}</p>
    </div>
</template>