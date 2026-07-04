<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        title: string;
        subtitle?: string;
        divider?: boolean;
    }>(),
    { divider: true },
);
</script>

<template>
    <div
        class="flex items-start justify-between gap-4 pb-5"
        :class="props.divider && 'border-b border-border-subtle'"
    >
        <div class="flex items-start gap-4">
            <slot name="avatar" />
            <div class="flex flex-col gap-0.5">
                <h1 class="text-xl font-semibold text-primary">{{ title }}</h1>
                <p v-if="subtitle" class="text-sm text-tertiary">{{ subtitle }}</p>
                <div v-if="$slots.meta" class="mt-1.5">
                    <slot name="meta" />
                </div>
            </div>
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
