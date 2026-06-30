<script setup lang="ts">
import { ref } from 'vue';
import DateInput from '@/components/ui/date-input/DateInput.vue';

const date = ref('');
</script>

<template>
    <DateInput v-model="date" :end-year="2030" />
    <p class="text-sm text-secondary mt-3">Value: {{ date || '—' }}</p>

</template>
