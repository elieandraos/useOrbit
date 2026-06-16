<script lang="ts" setup>
import type { InertiaLinkProps } from "@inertiajs/vue3"
import type { HTMLAttributes } from "vue"
import { Link } from "@inertiajs/vue3"
import { computed } from "vue"
import { cn, toUrl } from "@/lib/utils"

const props = defineProps<{
  href: NonNullable<InertiaLinkProps["href"]>
  class?: HTMLAttributes["class"]
}>()

const isExternal = computed(() => toUrl(props.href).startsWith("http"))
</script>

<template>
  <a
    v-if="isExternal"
    data-slot="breadcrumb-link"
    :href="toUrl(href)"
    target="_blank"
    rel="noopener noreferrer"
    :class="cn('hover:text-foreground transition-colors', props.class)"
  >
    <slot />
  </a>
  <Link
    v-else
    data-slot="breadcrumb-link"
    :href="href"
    :class="cn('hover:text-foreground transition-colors', props.class)"
  >
    <slot />
  </Link>
</template>
