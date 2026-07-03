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
    data-slot="card-footer"
    :class="
      cn(
        'flex items-center px-6 py-6',
        props.bordered && 'border-t border-border',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
