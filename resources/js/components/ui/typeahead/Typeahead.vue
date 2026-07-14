<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { ChevronDown } from "@lucide/vue"
import { computed, ref, useTemplateRef, watch } from "vue"
import { onClickOutside, useDebounceFn, useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"
import Spinner from "@/components/ui/spinner/Spinner.vue"

export interface TypeaheadOption {
    value: number | string
    label: string
}

defineOptions({ inheritAttrs: false })

interface Props {
    modelValue?: number | string | null
    options?: TypeaheadOption[]
    search?: (query: string) => Promise<TypeaheadOption[]>
    loading?: boolean
    initialLabel?: string | null
    debounce?: number
    placeholder?: string
    name?: string
    size?: "sm" | "md"
    disabled?: boolean
    class?: HTMLAttributes["class"]
}

const props = withDefaults(defineProps<Props>(), {
    size: "md",
    debounce: 300,
})

const emits = defineEmits<{
    (e: "update:modelValue", payload: number | string | null): void
}>()

const modelValue = useVModel(props, "modelValue", emits, { passive: true })

const wrapperRef = useTemplateRef<HTMLElement>("wrapperRef")
const inputRef = useTemplateRef<HTMLInputElement>("inputRef")

const query = ref("")
const open = ref(false)
const highlightedIndex = ref(-1)
const asyncOptions = ref<TypeaheadOption[]>([])
const searching = ref(false)

const optionPool = computed(() => props.options ?? asyncOptions.value)

const visibleOptions = computed(() => {
    if (props.options) {
        const needle = query.value.trim().toLowerCase()

        return needle ? props.options.filter((option) => option.label.toLowerCase().includes(needle)) : props.options
    }

    return asyncOptions.value
})

const isLoading = computed(() => props.loading || searching.value)

const selectedLabel = computed(() => {
    if (modelValue.value === null || modelValue.value === undefined || modelValue.value === "") {
        return ""
    }

    const match = optionPool.value.find((option) => String(option.value) === String(modelValue.value))

    return match?.label ?? props.initialLabel ?? ""
})

watch(
    selectedLabel,
    (label) => {
        if (!open.value) {
            query.value = label
        }
    },
    { immediate: true },
)

let requestId = 0

async function runSearch(value: string) {
    if (!props.search) {
        return
    }

    const currentRequestId = ++requestId
    searching.value = true

    try {
        const results = await props.search(value)

        if (currentRequestId === requestId) {
            asyncOptions.value = results
        }
    } finally {
        if (currentRequestId === requestId) {
            searching.value = false
        }
    }
}

const debouncedSearch = useDebounceFn(runSearch, () => props.debounce)

function openDropdown() {
    open.value = true
    highlightedIndex.value = -1

    if (props.search) {
        debouncedSearch(query.value)
    }
}

function onFocus() {
    openDropdown()
    inputRef.value?.select()
}

function onInput(event: Event) {
    query.value = (event.target as HTMLInputElement).value
    open.value = true
    highlightedIndex.value = -1

    if (props.search) {
        debouncedSearch(query.value)
    }
}

function selectOption(option: TypeaheadOption) {
    modelValue.value = option.value
    query.value = option.label
    open.value = false
    highlightedIndex.value = -1
}

function closeDropdown() {
    open.value = false
    highlightedIndex.value = -1
    query.value = selectedLabel.value
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === "ArrowDown") {
        event.preventDefault()

        if (!open.value) {
            openDropdown()
            return
        }

        highlightedIndex.value = Math.min(highlightedIndex.value + 1, visibleOptions.value.length - 1)
    } else if (event.key === "ArrowUp") {
        event.preventDefault()
        highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0)
    } else if (event.key === "Enter") {
        if (open.value && highlightedIndex.value >= 0) {
            event.preventDefault()
            const option = visibleOptions.value[highlightedIndex.value]

            if (option) {
                selectOption(option)
            }
        }
    } else if (event.key === "Escape") {
        closeDropdown()
        inputRef.value?.blur()
    }
}

onClickOutside(wrapperRef, closeDropdown)

const wrapperClass = computed(() =>
    cn(
        "relative flex items-center w-full rounded-[10px] border border-border bg-transparent transition-[color,box-shadow]",
        "focus-within:ring-[3px] focus-within:ring-accent-ring",
        props.size === "sm" ? "h-7 text-xs" : "h-9 text-sm",
        props.disabled && "opacity-50 cursor-not-allowed pointer-events-none",
        props.class,
    ),
)
</script>

<template>
    <div ref="wrapperRef" data-slot="typeahead" class="relative">
        <div :class="wrapperClass">
            <input
                ref="inputRef"
                v-bind="$attrs"
                :value="query"
                :placeholder="placeholder"
                :disabled="disabled"
                autocomplete="off"
                class="appearance-none w-full h-full bg-transparent outline-none px-3 pr-8 text-primary placeholder:text-tertiary"
                @input="onInput"
                @focus="onFocus"
                @keydown="onKeydown"
            />
            <Spinner v-if="isLoading" class="absolute right-3 size-4 text-tertiary" />
            <ChevronDown v-else class="absolute right-3 size-4 text-tertiary pointer-events-none shrink-0" />
        </div>

        <ul
            v-if="open"
            class="absolute z-20 mt-1 w-full max-h-60 overflow-y-auto rounded-[10px] border border-border bg-surface p-1 shadow-lg"
        >
            <li v-if="!isLoading && visibleOptions.length === 0" class="px-2 py-1.5 text-sm text-tertiary">No results found</li>
            <li
                v-for="(option, index) in visibleOptions"
                :key="option.value"
                :class="
                    cn(
                        'flex items-center rounded-[6px] px-2 py-1.5 text-sm cursor-pointer transition-colors text-primary',
                        index === highlightedIndex ? 'bg-sunken' : 'hover:bg-sunken',
                    )
                "
                @mousedown.prevent
                @click="selectOption(option)"
                @mouseenter="highlightedIndex = index"
            >
                {{ option.label }}
            </li>
        </ul>

        <input type="hidden" :name="name" :value="modelValue ?? ''" />
    </div>
</template>