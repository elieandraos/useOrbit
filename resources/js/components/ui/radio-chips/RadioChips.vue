<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed } from 'vue'
import { cn } from '@/lib/utils'

type Option = string | { label: string; value: string }

interface Props {
    options: Option[]
    modelValue?: string
    name?: string
    direction?: 'horizontal' | 'vertical'
    size?: 'sm' | 'md'
    class?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), {
    direction: 'horizontal',
    size: 'md',
})

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const normalizedOptions = computed(() =>
    props.options.map((opt) => (typeof opt === 'string' ? { label: opt, value: opt } : opt)),
)

function select(value: string) {
    emit('update:modelValue', value)
}
</script>

<template>
    <div
        :class="
            cn(
                'flex gap-2',
                direction === 'vertical' ? 'flex-col' : 'flex-row flex-wrap',
                props.class,
            )
        "
    >
        <button
            v-for="option in normalizedOptions"
            :key="option.value"
            type="button"
            :class="
                cn(
                    'inline-flex items-center gap-2 rounded-[10px] border font-medium transition-colors cursor-pointer',
                    size === 'sm' ? 'h-8 px-3 text-xs' : 'h-10 px-4 text-sm',
                    modelValue === option.value
                        ? 'bg-accent-bg border-accent text-accent'
                        : 'bg-surface border-border text-secondary hover:text-primary',
                )
            "
            @click="select(option.value)"
        >
            <span
                :class="
                    cn(
                        'rounded-full border-2 shrink-0 flex items-center justify-center transition-colors',
                        size === 'sm' ? 'size-3' : 'size-3.5',
                        modelValue === option.value ? 'border-accent' : 'border-input',
                    )
                "
            >
                <span
                    v-if="modelValue === option.value"
                    class="rounded-full bg-accent"
                    :class="size === 'sm' ? 'size-1.5' : 'size-2'"
                />
            </span>

            {{ option.label }}
        </button>
        <input v-if="name" type="hidden" :name="name" :value="modelValue" />
    </div>
</template>
