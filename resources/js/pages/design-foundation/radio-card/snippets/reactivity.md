<script setup lang="ts">
import { ref } from 'vue';
import { RadioCard } from '@/components/ui/radio-card';

const plan = ref('starter');
const plans = [
    { label: 'Starter', value: 'starter' },
    { label: 'Pro', value: 'pro' },
];
</script>

<template>
    <div class="flex flex-col gap-4">
        <RadioCard v-model="plan" :options="plans" />
        <p class="text-sm text-secondary">Selected: {{ plan }}</p>
    </div>
</template>
