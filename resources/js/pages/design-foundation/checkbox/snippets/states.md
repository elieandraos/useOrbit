<script setup lang="ts">
import { ref } from 'vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';

const unchecked = ref(false);
const checked = ref(true);
</script>

<template>
    <Checkbox v-model="unchecked" />
    <Checkbox v-model="checked" />
</template>
