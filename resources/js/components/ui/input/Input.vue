<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { computed, useTemplateRef } from "vue"
import { useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"

defineOptions({ inheritAttrs: false })

interface Props {
  defaultValue?: string | number
  modelValue?: string | number
  size?: "sm" | "md"
  disabled?: boolean
  class?: HTMLAttributes["class"]
}

const props = withDefaults(defineProps<Props>(), {
  size: "md",
})

const emits = defineEmits<{
  (e: "update:modelValue", payload: string | number): void
}>()

const modelValue = useVModel(props, "modelValue", emits, {
  passive: true,
  defaultValue: props.defaultValue,
})

const inputRef = useTemplateRef<HTMLInputElement>("inputRef")

defineExpose({
  focus: () => inputRef.value?.focus(),
})

const wrapperClass = computed(() =>
  cn(
    "flex items-center w-full rounded-[10px] border border-border bg-transparent transition-[color,box-shadow]",
    "focus-within:ring-[3px] focus-within:ring-accent-ring",
    "[&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0 [&_svg]:text-tertiary",
    props.size === "sm" ? "h-7 px-2 gap-1.5 text-xs" : "h-9 px-3 gap-2 text-sm",
    props.disabled && "opacity-50 cursor-not-allowed pointer-events-none",
    props.class,
  ),
)
</script>

<template>
  <div data-slot="input" :class="wrapperClass">
    <slot name="leading" />
    <input
      ref="inputRef"
      v-bind="$attrs"
      v-model="modelValue"
      :disabled="disabled"
      class="flex-1 min-w-0 h-full bg-transparent outline-none placeholder:text-tertiary text-primary"
    />
    <slot name="trailing" />
  </div>
</template>
