import { cn } from "@/lib/utils"

export { default as Button } from "./Button.vue"

const base =
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-[10px] text-sm font-medium transition-all cursor-pointer disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:ring-2 focus-visible:ring-accent-ring"

const variantClasses = {
  primary: "bg-accent text-accent-fg hover:bg-accent-hover",
  secondary: "bg-surface text-primary border border-border hover:bg-sunken",
  ghost: "text-primary hover:bg-sunken",
  destructive:
    "bg-danger text-white hover:bg-danger/90 focus-visible:ring-danger/25",
}

const sizeClasses = {
  sm: "h-7 px-3",
  md: "h-[34px] px-4",
  lg: "h-10 px-5",
}

export type ButtonVariants = {
  variant?: keyof typeof variantClasses
  size?: keyof typeof sizeClasses
}

export function buttonVariants({
  variant = "primary",
  size = "md",
}: ButtonVariants = {}) {
  return cn(base, variantClasses[variant], sizeClasses[size])
}
