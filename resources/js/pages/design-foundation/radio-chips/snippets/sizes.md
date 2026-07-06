<script setup lang="ts">
import { ref } from 'vue';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';

const sm = ref('Female');
const md = ref('Female');
const options = ['Female', 'Male', 'Non-binary', 'Prefer not to say'];
</script>

<template>
    <div class="flex flex-col gap-4">
        <RadioChips v-model="sm" :options="options" size="sm" />
        <RadioChips v-model="md" :options="options" size="md" />
    </div>
</template>
