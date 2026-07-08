<script setup lang="ts">
import Switch from '@/components/ui/switch/Switch.vue';
</script>

<template>
    <div class="flex items-center gap-6">
        <Switch :model-value="false" disabled />
        <Switch :model-value="true" disabled />
    </div>
</template>
