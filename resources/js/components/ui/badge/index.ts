import { cn } from "@/lib/utils"

export { default as Badge } from "./Badge.vue"

const base =
    "inline-flex items-center gap-1.5 rounded-pill px-2 py-1 text-xs font-medium w-fit whitespace-nowrap shrink-0"

const toneClasses = {
    neutral: "bg-sunken text-secondary",
    success: "bg-success-bg text-success",
    warning: "bg-warning-bg text-warning",
    danger: "bg-danger-bg text-danger",
    info: "bg-info-bg text-info",
    accent: "bg-accent-bg text-accent",
}

export type BadgeVariants = {
    tone?: keyof typeof toneClasses
}

export function badgeVariants({ tone = "neutral" }: BadgeVariants = {}) {
    return cn(base, toneClasses[tone])
}
