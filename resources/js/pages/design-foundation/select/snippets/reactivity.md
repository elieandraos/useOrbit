<script setup lang="ts">
import { ref } from 'vue';
import Select from '@/components/ui/select/Select.vue';

const selected = ref('');
</script>

<template>
    <Select v-model="selected" placeholder="Pick a fruit">
        <option value="apple">Apple</option>
        <option value="banana">Banana</option>
        <option value="cherry">Cherry</option>
    </Select>
    <p class="text-sm text-secondary">Value: {{ selected }}</p>
</template>
