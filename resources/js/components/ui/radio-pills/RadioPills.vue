<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed } from 'vue'
import { cn } from '@/lib/utils'

type Option = string | { label: string; value: string }

interface Props {
    options: Option[]
    modelValue?: string
    name?: string
    size?: 'sm' | 'md'
    class?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), {
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
    <div :class="cn('flex flex-row flex-wrap gap-2', props.class)">
        <button
            v-for="option in normalizedOptions"
            :key="option.value"
            type="button"
            :class="
                cn(
                    'inline-flex items-center rounded-pill border font-medium transition-colors cursor-pointer',
                    size === 'sm' ? 'h-7 px-3 text-xs' : 'h-8 px-3.5 text-sm',
                    modelValue === option.value
                        ? 'bg-accent-bg border-accent text-accent'
                        : 'bg-surface border-border text-secondary hover:text-primary',
                )
            "
            @click="select(option.value)"
        >
            {{ option.label }}
        </button>
        <input v-if="name" type="hidden" :name="name" :value="modelValue" />
    </div>
</template>