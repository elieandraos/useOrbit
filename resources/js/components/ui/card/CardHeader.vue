<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
  bordered?: boolean
}>()
</script>

<template>
  <div
    data-slot="card-header"
    :class="
      cn(
        'relative flex items-center gap-1.5 px-6 py-[18px]',
        props.bordered && 'border-b border-border',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
