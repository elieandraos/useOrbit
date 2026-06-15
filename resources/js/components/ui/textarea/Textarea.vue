<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { computed, useTemplateRef } from "vue"
import { useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"

defineOptions({ inheritAttrs: false })

interface Props {
  defaultValue?: string
  modelValue?: string
  autoGrow?: boolean
  disabled?: boolean
  class?: HTMLAttributes["class"]
}

const props = withDefaults(defineProps<Props>(), {
  autoGrow: false,
})

const emits = defineEmits<{
  (e: "update:modelValue", payload: string): void
}>()

const modelValue = useVModel(props, "modelValue", emits, {
  passive: true,
  defaultValue: props.defaultValue,
})

const textareaRef = useTemplateRef<HTMLTextAreaElement>("textareaRef")

defineExpose({
  focus: () => textareaRef.value?.focus(),
})

const textareaClass = computed(() =>
  cn(
    "w-full rounded-[10px] border border-border bg-transparent px-3 py-2 text-sm",
    "outline-none placeholder:text-tertiary text-primary",
    "transition-[color,box-shadow]",
    "focus:ring-[3px] focus:ring-accent-ring",
    props.autoGrow ? "[field-sizing:content] resize-none" : "resize-y",
    props.disabled && "opacity-50 cursor-not-allowed pointer-events-none",
    props.class,
  ),
)
</script>

<template>
  <textarea
    ref="textareaRef"
    data-slot="textarea"
    v-bind="$attrs"
    v-model="modelValue"
    :disabled="disabled"
    :class="textareaClass"
  />
</template>
