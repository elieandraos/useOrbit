<script setup lang="ts">
import { ref } from 'vue';
import { Typeahead } from '@/components/ui/typeahead';
import type { TypeaheadOption } from '@/components/ui/typeahead';

const options: TypeaheadOption[] = [
    { value: 1, label: 'Marie Khalil' },
    { value: 2, label: 'Karim Fares' },
];

const value = ref<number | string | null>(null);
</script>

<template>
    <Typeahead v-model="value" :options="options" placeholder="Assign to" disabled />
</template>
