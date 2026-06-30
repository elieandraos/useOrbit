<script setup lang="ts">
import { ref } from 'vue';
import DateInput from '@/components/ui/date-input/DateInput.vue';

const date = ref('1965-06-10');
</script>

<template>
    <DateInput v-model="date" :start-year="1950" />
    <p class="text-sm text-secondary mt-3">Value: {{ date || '—' }}</p>
</template>
