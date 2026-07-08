<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed, useId } from 'vue'
import { cn } from '@/lib/utils'

interface Props {
    label: string
    description?: string
    id?: string
    class?: HTMLAttributes['class']
}

const props = defineProps<Props>()

const generatedId = useId()
const fieldId = computed(() => props.id ?? generatedId)
</script>

<template>
    <div
        :class="
            cn(
                'flex items-center gap-3.5 rounded-lg border border-border bg-surface px-4 py-3.5',
                props.class,
            )
        "
    >
        <div class="min-w-0 flex-1">
            <label
                :for="fieldId"
                class="cursor-pointer text-sm font-semibold text-primary"
                >{{ label }}</label
            >
            <p v-if="description" class="mt-0.5 text-[11.5px] text-tertiary">
                {{ description }}
            </p>
        </div>

        <slot :id="fieldId" />
    </div>
</template>
