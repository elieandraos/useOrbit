<script setup lang="ts">
import { AtSign } from '@lucide/vue';
import Input from '@/components/ui/input/Input.vue';
</script>

<template>
    <Input placeholder="Enter email">
        <template #trailing><AtSign /></template>
    </Input>
</template>
