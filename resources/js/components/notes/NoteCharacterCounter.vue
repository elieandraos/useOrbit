<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    length: number;
    max: number;
}>();

const remaining = computed(() => props.max - props.length);

const toneClass = computed(() => {
    if (remaining.value < 0) {
        return 'text-danger';
    }

    if (remaining.value <= 20) {
        return 'text-warning';
    }

    return 'text-secondary';
});
</script>

<template>
    <span class="font-mono text-[13px]" :class="toneClass">
        {{ length }}/{{ max }}
    </span>
</template>
