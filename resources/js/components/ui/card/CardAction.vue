<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="card-action"
    :class="cn('absolute inset-y-0 right-6 flex items-center', props.class)"
  >
    <slot />
  </div>
</template>
