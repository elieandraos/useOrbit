<script setup lang="ts">
import { ref } from 'vue';
import Switch from '@/components/ui/switch/Switch.vue';

const md = ref(true);
const lg = ref(true);
</script>

<template>
    <div class="flex items-center gap-6">
        <Switch v-model="md" size="md" />
        <Switch v-model="lg" size="lg" />
    </div>
</template>
