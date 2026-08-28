<script setup lang="ts">
withDefaults(
    defineProps<{
        label: string;
        value?: string | null;
        mono?: boolean;
        lowercase?: boolean;
        multiline?: boolean;
        accent?: boolean;
    }>(),
    {
        value: null,
        mono: false,
        lowercase: false,
        multiline: false,
        accent: false,
    },
);
</script>

<template>
    <div class="flex min-w-0 flex-col gap-1">
        <p
            class="font-mono text-[10.5px] tracking-[0.06em] text-tertiary uppercase"
        >
            {{ label }}
        </p>
        <p
            class="text-[13.5px]"
            :class="[
                accent ? 'text-accent' : 'text-primary',
                mono && 'font-mono',
                lowercase ? 'lowercase' : 'capitalize',
                multiline ? 'whitespace-pre-line' : 'truncate',
            ]"
        >
            <template v-if="value">{{ value }}</template>
            <span v-else class="text-tertiary">—</span>
        </p>
    </div>
</template>
