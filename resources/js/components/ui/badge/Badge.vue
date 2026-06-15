<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import type { BadgeVariants } from "."
import { computed } from "vue"
import { cn } from "@/lib/utils"
import { badgeVariants } from "."

interface Props {
    tone?: BadgeVariants["tone"]
    dot?: boolean
    class?: HTMLAttributes["class"]
}

const props = defineProps<Props>()

const dotColorClass = computed(
    () =>
        ({
            neutral: "bg-secondary",
            success: "bg-success",
            warning: "bg-warning",
            danger: "bg-danger",
            info: "bg-info",
            accent: "bg-accent",
        })[props.tone ?? "neutral"] ?? "bg-secondary",
)
</script>

<template>
    <span data-slot="badge" :class="cn(badgeVariants({ tone }), props.class)">
        <span v-if="dot" :class="cn('size-1.5 rounded-full shrink-0', dotColorClass)" />
        <slot />
    </span>
</template>
