<script setup lang="ts">
import { ref } from 'vue';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';

const gender = ref('Female');
</script>

<template>
    <div class="flex flex-col gap-4">
        <RadioChips
            v-model="gender"
            :options="['Female', 'Male', 'Non-binary', 'Prefer not to say']"
        />
        <p class="text-sm text-secondary">Selected: {{ gender }}</p>
    </div>
</template>
