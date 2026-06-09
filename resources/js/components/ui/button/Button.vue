<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import type { ButtonVariants } from "."
import { computed } from "vue"
import { cn } from "@/lib/utils"
import { buttonVariants } from "."

interface Props {
  variant?: ButtonVariants["variant"]
  size?: ButtonVariants["size"]
  full?: boolean
  class?: HTMLAttributes["class"]
}

const props = defineProps<Props>()

const computedClass = computed(() =>
  cn(buttonVariants({ variant: props.variant, size: props.size }), props.full && "w-full", props.class),
)
</script>

<template>
  <button data-slot="button" v-bind="$attrs" :class="computedClass">
    <slot name="leading" />
    <slot />
    <slot name="trailing" />
  </button>
</template>
