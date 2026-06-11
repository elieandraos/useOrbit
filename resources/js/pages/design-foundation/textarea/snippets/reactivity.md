<script setup lang="ts">
import { ref } from 'vue';
import Textarea from '@/components/ui/textarea/Textarea.vue';

const value = ref('');
</script>

<template>
    <Textarea v-model="value" placeholder="Type something..." />
    <p>Value: {{ value }}</p>
</template>
