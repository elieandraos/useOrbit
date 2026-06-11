<script setup lang="ts">
import { Plus } from '@lucide/vue';
import Button from '@/components/ui/button/Button.vue';
</script>

<template>
    <Button>
        <template #leading><Plus /></template>
        Add item
    </Button>
</template>
