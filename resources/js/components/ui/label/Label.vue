<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

interface Props {
    for?: string
    required?: boolean
    class?: HTMLAttributes["class"]
}

const props = defineProps<Props>()
</script>

<template>
    <label
        data-slot="label"
        :for="props.for"
        :class="cn('flex items-center gap-1 text-sm font-medium text-primary select-none', props.class)"
    >
        <slot />
        <span v-if="required" class="text-danger">*</span>
    </label>
</template>
