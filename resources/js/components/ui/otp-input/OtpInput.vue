<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed, onMounted, ref, watch } from 'vue'
import { cn } from '@/lib/utils'

interface Props {
    modelValue?: string
    name?: string
    id?: string
    length?: number
    autofocus?: boolean
    disabled?: boolean
    class?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), {
    length: 6,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

function digitsFrom(value?: string): string[] {
    const chars = (value ?? '').replace(/\D/g, '').slice(0, props.length).split('')

    return Array.from({ length: props.length }, (_, i) => chars[i] ?? '')
}

const digits = ref<string[]>(digitsFrom(props.modelValue))
const inputs = ref<(HTMLInputElement | null)[]>([])

function setInputRef(el: unknown, index: number) {
    inputs.value[index] = el as HTMLInputElement | null
}

function focusInput(index: number) {
    inputs.value[index]?.focus()
}

watch(() => props.modelValue, (value) => {
    const incoming = digitsFrom(value)
    if (incoming.join('') !== digits.value.join('')) {
        digits.value = incoming
    }
})

const combined = computed(() => digits.value.join(''))

watch(combined, (value) => {
    emit('update:modelValue', value)
})

function distributeFrom(startIndex: number, chars: string[]) {
    for (let offset = 0; offset < props.length - startIndex && offset < chars.length; offset++) {
        digits.value[startIndex + offset] = chars[offset]
    }

    focusInput(Math.min(startIndex + chars.length, props.length - 1))
}

function handleInput(index: number, event: Event) {
    const target = event.target as HTMLInputElement
    const digitsOnly = target.value.replace(/\D/g, '')

    if (digitsOnly.length > 1) {
        distributeFrom(index, digitsOnly.split(''))
        target.value = digits.value[index] ?? ''
        return
    }

    digits.value[index] = digitsOnly
    target.value = digitsOnly

    if (digitsOnly && index < props.length - 1) {
        focusInput(index + 1)
    }
}

function handleKeydown(index: number, event: KeyboardEvent) {
    if (event.key === 'Backspace' && !digits.value[index] && index > 0) {
        event.preventDefault()
        digits.value[index - 1] = ''
        focusInput(index - 1)
        return
    }

    if (event.key === 'ArrowLeft' && index > 0) {
        event.preventDefault()
        focusInput(index - 1)
        return
    }

    if (event.key === 'ArrowRight' && index < props.length - 1) {
        event.preventDefault()
        focusInput(index + 1)
    }
}

function handlePaste(index: number, event: ClipboardEvent) {
    const pasted = event.clipboardData?.getData('text') ?? ''
    const chars = pasted.replace(/\D/g, '').split('')

    if (!chars.length) {
        return
    }

    event.preventDefault()
    distributeFrom(index, chars)
}

function handleFocus(event: FocusEvent) {
    (event.target as HTMLInputElement).select()
}

onMounted(() => {
    if (props.autofocus) {
        focusInput(0)
    }
})
</script>

<template>
    <div role="group" :class="cn('flex gap-2', props.class)">
        <input
            v-for="(digit, index) in digits"
            :key="index"
            :ref="(el) => setInputRef(el, index)"
            :id="index === 0 ? id : undefined"
            type="text"
            inputmode="numeric"
            pattern="[0-9]*"
            autocomplete="one-time-code"
            maxlength="1"
            required
            :aria-label="`Digit ${index + 1} of ${length}`"
            :disabled="disabled"
            :value="digit"
            class="h-11 w-11 rounded-[10px] border border-border bg-transparent text-center text-lg font-medium text-primary outline-none transition-[color,box-shadow] focus:ring-[3px] focus:ring-accent-ring disabled:pointer-events-none disabled:opacity-50"
            @input="handleInput(index, $event)"
            @keydown="handleKeydown(index, $event)"
            @paste="handlePaste(index, $event)"
            @focus="handleFocus"
        />
        <input v-if="name" type="hidden" :name="name" :value="combined" />
    </div>
</template>
