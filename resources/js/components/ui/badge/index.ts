import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

export const badgeVariants = cva(
    "inline-flex items-center gap-1.5 rounded-pill px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0",
    {
        variants: {
            tone: {
                neutral: "bg-sunken text-secondary",
                success: "bg-success-bg text-success",
                warning: "bg-warning-bg text-warning",
                danger: "bg-danger-bg text-danger",
                info: "bg-info-bg text-info",
                accent: "bg-accent-bg text-accent",
            },
        },
        defaultVariants: {
            tone: "neutral",
        },
    },
)

export type BadgeVariants = VariantProps<typeof badgeVariants>
