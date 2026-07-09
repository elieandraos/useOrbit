<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed, ref, watch } from 'vue'
import { cn } from '@/lib/utils'

interface Props {
    modelValue?: boolean
    disabled?: boolean
    size?: 'md' | 'lg'
    id?: string
    name?: string
    tabindex?: number
    class?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
})

const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>()

const internalChecked = ref(props.modelValue ?? false)

watch(
    () => props.modelValue,
    (val) => {
        if (val !== undefined) {
            internalChecked.value = val
        }
    },
)

const isChecked = computed(() => (props.modelValue !== undefined ? props.modelValue : internalChecked.value))

function toggle() {
    if (props.disabled) return
    internalChecked.value = !internalChecked.value
    emit('update:modelValue', internalChecked.value)
}
</script>

<template>
    <span
        data-slot="switch"
        :class="cn('relative inline-flex shrink-0', size === 'lg' ? 'h-6 w-11' : 'h-5 w-9', props.class)"
    >
        <input
            type="checkbox"
            :id="id"
            :name="name"
            :checked="isChecked"
            :disabled="disabled"
            :tabindex="tabindex"
            class="peer absolute inset-0 size-full cursor-pointer opacity-0 disabled:cursor-not-allowed"
            @change="toggle"
        />
        <span
            aria-hidden="true"
            :class="
                cn(
                    'pointer-events-none absolute inset-0 rounded-pill transition-colors',
                    'peer-focus-visible:ring-[3px] peer-focus-visible:ring-accent-ring',
                    isChecked ? 'bg-accent' : 'bg-border-strong peer-hover:bg-tertiary',
                    disabled && 'opacity-50',
                )
            "
        >
            <span
                :class="
                    cn(
                        'absolute top-0.5 left-0.5 rounded-full bg-white shadow-[0_1px_2px_rgba(0,0,0,0.18),0_1px_1px_rgba(0,0,0,0.06)] transition-transform duration-150',
                        size === 'lg' ? 'size-5' : 'size-4',
                        isChecked && (size === 'lg' ? 'translate-x-5' : 'translate-x-4'),
                    )
                "
            />
        </span>
    </span>
</template>
