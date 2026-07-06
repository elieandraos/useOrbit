<script setup lang="ts">
import { ref } from 'vue';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';

const gender = ref('Female');
</script>

<template>
    <RadioChips
        v-model="gender"
        :options="['Female', 'Male', 'Non-binary', 'Prefer not to say']"
        direction="vertical"
    />
</template>
