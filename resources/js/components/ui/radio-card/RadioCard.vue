<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

interface Option {
    label: string
    value: string
    desc?: string
}

interface Props {
    options: Option[]
    modelValue?: string
    name?: string
    class?: HTMLAttributes['class']
}

const props = defineProps<Props>()

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

function select(value: string) {
    emit('update:modelValue', value)
}
</script>

<template>
    <div :class="cn('flex flex-col gap-2', props.class)">
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            :class="
                cn(
                    'flex cursor-pointer items-start gap-2.5 rounded-md border px-3.5 py-3 text-left transition-colors',
                    modelValue === option.value
                        ? 'border-accent bg-accent-bg'
                        : 'border-border bg-surface hover:border-border-strong',
                )
            "
            @click="select(option.value)"
        >
            <span
                :class="
                    cn(
                        'mt-0.5 flex size-[18px] shrink-0 items-center justify-center rounded-full border-[1.5px] transition-colors',
                        modelValue === option.value
                            ? 'border-accent bg-accent'
                            : 'border-input bg-surface',
                    )
                "
            >
                <span
                    v-if="modelValue === option.value"
                    class="size-1.5 rounded-full bg-white"
                />
            </span>
            <span class="min-w-0">
                <span class="block text-[13.5px] font-semibold text-primary">{{
                    option.label
                }}</span>
                <span
                    v-if="option.desc"
                    class="mt-0.5 block text-xs text-secondary"
                    >{{ option.desc }}</span
                >
            </span>
        </button>
        <input v-if="name" type="hidden" :name="name" :value="modelValue" />
    </div>
</template>
