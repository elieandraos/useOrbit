<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div
        :class="
            cn(
                'flex size-[26px] shrink-0 items-center justify-center rounded-[6px] bg-accent text-accent-fg',
                props.class,
            )
        "
    >
        <span class="text-sm font-bold leading-none tracking-tight">O</span>
    </div>
</template>
