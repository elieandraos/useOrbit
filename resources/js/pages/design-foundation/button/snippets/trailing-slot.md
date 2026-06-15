<script setup lang="ts">
import { ArrowRight } from '@lucide/vue';
import Button from '@/components/ui/button/Button.vue';
</script>

<template>
    <Button>
        Continue
        <template #trailing><ArrowRight /></template>
    </Button>
</template>
