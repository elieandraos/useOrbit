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
        class="flex flex-col items-start gap-4 pb-5 sm:flex-row sm:justify-between"
        :class="props.divider && 'border-b border-border-subtle'"
    >
        <div class="flex items-start gap-4">
            <slot name="avatar" />
            <div class="flex flex-col gap-0.5">
                <h1 class="text-lg font-semibold text-primary sm:text-xl">
                    {{ title }}
                </h1>
                <p v-if="subtitle" class="text-sm text-tertiary">
                    {{ subtitle }}
                </p>
                <div v-if="$slots.meta" class="mt-1.5">
                    <slot name="meta" />
                </div>
            </div>
        </div>
        <div
            v-if="$slots.actions"
            class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:shrink-0"
        >
            <slot name="actions" />
        </div>
    </div>
</template>
