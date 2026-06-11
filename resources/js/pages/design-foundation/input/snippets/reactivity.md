<script setup lang="ts">
import { ref } from 'vue';
import Input from '@/components/ui/input/Input.vue';

const value = ref('');
</script>

<template>
    <Input v-model="value" placeholder="Type something..." />
    <p>Value: {{ value }}</p>
</template>
