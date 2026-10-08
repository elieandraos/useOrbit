<script lang="ts">
export interface TypeaheadOption {
    value: number | string
    label: string
}
</script>

<script setup lang="ts" generic="T extends TypeaheadOption">
import type { HTMLAttributes } from "vue"
import { ChevronDown, X } from "@lucide/vue"
import { computed, nextTick, ref, shallowRef, useTemplateRef, watch } from "vue"
import { onClickOutside, useDebounceFn, useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"
import Spinner from "@/components/ui/spinner/Spinner.vue"

defineOptions({ inheritAttrs: false })

interface Props {
    modelValue?: number | string | null
    options?: T[]
    search?: (query: string) => Promise<T[]>
    /**
     * Async mode only: below this many (trimmed) characters no search runs and no results show.
     */
    minQueryLength?: number
    loading?: boolean
    initialLabel?: string | null
    /**
     * The chosen option's label, bindable with `v-model:label`.
     */
    label?: string | null
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
    minQueryLength: 0,
})

const emits = defineEmits<{
    (e: "update:modelValue", payload: number | string | null): void
    (e: "update:label", payload: string | null): void
}>()

const slots = defineSlots<{
    /**
     * Replaces the input while a value is selected; a clear button is rendered beside it.
     */
    selected?: (props: { option: T | null; label: string; clear: () => void }) => unknown
    /**
     * Replaces the label inside each result row.
     */
    option?: (props: { option: T; index: number; highlighted: boolean }) => unknown
}>()

const modelValue = useVModel(props, "modelValue", emits, { passive: true })

const wrapperRef = useTemplateRef<HTMLElement>("wrapperRef")
const inputRef = useTemplateRef<HTMLInputElement>("inputRef")

const query = ref("")
const open = ref(false)
const highlightedIndex = ref(-1)
const asyncOptions = shallowRef<T[]>([])
const chosenOption = shallowRef<T | null>(null)
const searching = ref(false)

const isAsync = computed(() => !props.options && Boolean(props.search))

const optionPool = computed(() => props.options ?? asyncOptions.value)

const meetsMinimumLength = computed(() => query.value.trim().length >= props.minQueryLength)

const visibleOptions = computed(() => {
    if (props.options) {
        const needle = query.value.trim().toLowerCase()

        return needle ? props.options.filter((option) => option.label.toLowerCase().includes(needle)) : props.options
    }

    return asyncOptions.value
})

const isLoading = computed(() => props.loading || searching.value)

const hasValue = computed(() => modelValue.value !== null && modelValue.value !== undefined && modelValue.value !== "")

const selectedOption = computed<T | null>(() => {
    if (!hasValue.value) {
        return null
    }

    const match = optionPool.value.find((option) => String(option.value) === String(modelValue.value))

    if (match) {
        return match
    }

    if (isAsync.value && chosenOption.value && String(chosenOption.value.value) === String(modelValue.value)) {
        return chosenOption.value
    }

    return null
})

const selectedLabel = computed(() => {
    if (!hasValue.value) {
        return ""
    }

    return selectedOption.value?.label ?? props.initialLabel ?? props.label ?? ""
})

const showSelectedState = computed(() => hasValue.value && Boolean(slots.selected))

const showMinimumLengthHint = computed(() => isAsync.value && props.minQueryLength > 0 && !meetsMinimumLength.value)

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

/**
 * Discards any scheduled or in-flight search so a late response can't bring back stale results.
 */
function invalidateSearch() {
    requestId++
    searching.value = false
    asyncOptions.value = []
}

async function runSearch(value: string, scheduledRequestId: number) {
    if (!props.search || scheduledRequestId !== requestId) {
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

function searchForQuery() {
    if (!props.search) {
        return
    }

    if (isAsync.value && !meetsMinimumLength.value) {
        invalidateSearch()

        return
    }

    debouncedSearch(query.value, requestId)
}

function openDropdown() {
    open.value = true
    highlightedIndex.value = -1

    searchForQuery()
}

function onFocus() {
    openDropdown()
    inputRef.value?.select()
}

function onInput(event: Event) {
    query.value = (event.target as HTMLInputElement).value
    open.value = true
    highlightedIndex.value = -1

    searchForQuery()
}

function selectOption(option: T) {
    chosenOption.value = option
    modelValue.value = option.value
    query.value = option.label
    open.value = false
    highlightedIndex.value = -1
    emits("update:label", option.label)
}

async function clear() {
    if (props.disabled) {
        return
    }

    invalidateSearch()
    chosenOption.value = null
    modelValue.value = null
    query.value = ""
    open.value = false
    highlightedIndex.value = -1
    emits("update:label", null)

    await nextTick()
    inputRef.value?.focus()
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
        <div v-if="showSelectedState" :class="wrapperClass" data-slot="typeahead-selected">
            <div class="flex min-w-0 flex-1 items-center gap-2 px-3 text-primary">
                <slot name="selected" :option="selectedOption" :label="selectedLabel" :clear="clear">
                    <span class="truncate">{{ selectedLabel }}</span>
                </slot>
            </div>
            <button
                type="button"
                aria-label="Clear selection"
                :disabled="disabled"
                class="mr-2 flex size-5 shrink-0 items-center justify-center rounded-[6px] text-tertiary transition-colors hover:bg-sunken hover:text-primary"
                @click="clear"
            >
                <X class="size-3.5" />
            </button>
        </div>

        <div v-else :class="wrapperClass">
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
            v-if="open && !showSelectedState"
            class="absolute z-20 mt-1 w-full max-h-60 overflow-y-auto rounded-[10px] border border-border bg-surface p-1 shadow-lg"
        >
            <li v-if="showMinimumLengthHint" class="px-2 py-1.5 text-sm text-tertiary">
                Type at least {{ minQueryLength }} {{ minQueryLength === 1 ? "character" : "characters" }} to search
            </li>
            <template v-else>
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
                    <slot name="option" :option="option" :index="index" :highlighted="index === highlightedIndex">
                        {{ option.label }}
                    </slot>
                </li>
            </template>
        </ul>

        <input type="hidden" :name="name" :value="modelValue ?? ''" />
    </div>
</template>
