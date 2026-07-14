<script setup lang="ts">
import { ref } from 'vue';
import { Typeahead } from '@/components/ui/typeahead';
import type { TypeaheadOption } from '@/components/ui/typeahead';

const options: TypeaheadOption[] = [{ value: 1, label: 'Marie Khalil' }];

const value = ref<number | string | null>(null);
</script>

<template>
    <Typeahead v-model="value" :options="options" placeholder="Search clients…" loading />
</template>
