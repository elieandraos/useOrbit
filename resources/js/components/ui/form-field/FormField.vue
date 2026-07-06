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
        <div class="flex items-center gap-0.5">
            <Label :for="props.for">{{ label }}</Label>
            <span v-if="required" class="text-sm text-danger">*</span>
            <span v-if="optional" class="text-xs text-tertiary ml-0.5">optional</span>
        </div>

        <slot />

        <p v-if="error" class="text-xs text-danger">{{ error }}</p>
        <p v-else-if="success" class="text-xs text-success">{{ success }}</p>
        <p v-else-if="helper" class="text-xs text-tertiary">{{ helper }}</p>
    </div>
</template>
