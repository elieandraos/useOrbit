<script setup lang="ts">
import { X } from '@lucide/vue';

defineProps<{
    name: string;
    removable?: boolean;
}>();

const emit = defineEmits<{
    remove: [];
}>();
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full border border-accent-ring bg-accent-bg py-1 pr-2.5 pl-2 font-mono text-[11.5px] font-medium text-accent"
    >
        <span class="size-1.5 shrink-0 rounded-full bg-accent" />
        {{ name }}
        <button
            v-if="removable"
            type="button"
            title="Remove tag"
            class="-ml-0.5 inline-flex shrink-0 items-center text-accent opacity-60 hover:opacity-100"
            @click="emit('remove')"
        >
            <X class="size-2.5" />
        </button>
    </span>
</template>
