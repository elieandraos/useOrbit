<script setup lang="ts">
withDefaults(
    defineProps<{
        orientation?: 'horizontal' | 'vertical';
    }>(),
    {
        orientation: 'horizontal',
    },
);
</script>

<template>
    <div
        data-slot="separator"
        :class="[
            'bg-border-subtle shrink-0',
            orientation === 'horizontal' ? 'h-px w-full' : 'h-full w-px',
        ]"
        :aria-orientation="orientation"
        role="separator"
    />
</template>
