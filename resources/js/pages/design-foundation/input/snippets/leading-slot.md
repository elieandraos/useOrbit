<script setup lang="ts">
import { Search } from '@lucide/vue';
import Input from '@/components/ui/input/Input.vue';
</script>

<template>
    <Input placeholder="Search...">
        <template #leading><Search /></template>
    </Input>
</template>
