<script setup lang="ts">
interface Props {
    title: string;
    subtitle?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="bg-surface border border-border rounded-lg shadow-card p-6 flex flex-col gap-6">
        <div class="flex flex-col gap-0.5">
            <div class="flex items-center gap-2">
                <h2 class="text-[15px] font-semibold text-primary">{{ title }}</h2>
                <slot name="badge" />
            </div>
            <p v-if="subtitle" class="text-sm text-tertiary">{{ subtitle }}</p>
        </div>

        <slot />
    </div>
</template>
