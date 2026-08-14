<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed, ref, watch } from 'vue'
import { Check } from '@lucide/vue'
import { cn } from '@/lib/utils'

interface Props {
    modelValue?: boolean
    disabled?: boolean
    id?: string
    name?: string
    value?: string | number
    tabindex?: number
    class?: HTMLAttributes['class']
}

const props = defineProps<Props>()

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
    <span data-slot="checkbox" :class="cn('relative inline-flex size-4 shrink-0', props.class)">
        <input
            type="checkbox"
            :id="id"
            :name="name"
            :value="value"
            :checked="isChecked"
            :disabled="disabled"
            :tabindex="tabindex"
            class="peer absolute inset-0 size-full opacity-0 cursor-pointer disabled:cursor-not-allowed"
            @change="toggle"
        />
        <span
            aria-hidden="true"
            :class="
                cn(
                    'size-4 rounded-[4px] border inline-flex items-center justify-center transition-colors pointer-events-none',
                    'peer-focus-visible:ring-[3px] peer-focus-visible:ring-indigo-500/30',
                    isChecked ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-background border-input',
                    disabled && 'opacity-50',
                )
            "
        >
            <Check v-if="isChecked" class="size-3.5" :stroke-width="3" />
        </span>
    </span>
</template>
