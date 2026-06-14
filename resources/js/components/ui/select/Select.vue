<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { ChevronDown } from "@lucide/vue"
import { computed } from "vue"
import { useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"

defineOptions({ inheritAttrs: false })

interface Props {
    defaultValue?: string
    modelValue?: string
    placeholder?: string
    size?: "sm" | "md"
    disabled?: boolean
    class?: HTMLAttributes["class"]
}

const props = withDefaults(defineProps<Props>(), {
    size: "md",
})

const emits = defineEmits<{
    (e: "update:modelValue", payload: string): void
}>()

const modelValue = useVModel(props, "modelValue", emits, {
    passive: true,
    // Default to "" when a placeholder is set so the native <select> matches
    // the value="" placeholder option on mount — otherwise it renders blank.
    defaultValue: props.defaultValue ?? (props.placeholder !== undefined ? "" : undefined),
})

const wrapperClass = computed(() =>
    cn(
        "relative flex items-center w-full rounded-[10px] border border-border bg-transparent transition-[color,box-shadow]",
        "focus-within:ring-[3px] focus-within:ring-accent-ring",
        props.size === "sm" ? "h-7 text-xs" : "h-9 text-sm",
        props.disabled && "opacity-50 cursor-not-allowed pointer-events-none",
        props.class,
    ),
)
</script>

<template>
    <div data-slot="select" :class="wrapperClass">
        <select
            v-bind="$attrs"
            v-model="modelValue"
            :disabled="disabled"
            :class="cn('appearance-none w-full h-full bg-transparent outline-none px-3 pr-8 cursor-pointer', modelValue ? 'text-primary' : 'text-tertiary')"
        >
            <option v-if="placeholder" value="" disabled hidden>{{ placeholder }}</option>
            <slot />
        </select>
        <ChevronDown class="absolute right-3 size-4 text-tertiary pointer-events-none shrink-0" />
    </div>
</template>
