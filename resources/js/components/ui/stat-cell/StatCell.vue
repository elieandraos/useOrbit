<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
}>();
</script>

<template>
    <div class="px-[18px] py-3.5">
        <p
            class="font-mono text-[10.5px] tracking-[0.06em] text-tertiary uppercase"
        >
            {{ label }}
        </p>
        <p class="mt-1 font-mono text-lg font-semibold text-primary">
            {{ value }}
        </p>
    </div>
</template>
