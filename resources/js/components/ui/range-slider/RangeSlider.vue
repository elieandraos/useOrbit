<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    min: number;
    max: number;
    modelValue: [number, number];
    step?: number;
    labelPrefix?: string;
    labelSuffix?: string;
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    step: 1,
    labelPrefix: '',
    labelSuffix: '',
});

const emit = defineEmits<{
    'update:modelValue': [value: [number, number]];
}>();

const low = computed(() => props.modelValue[0]);
const high = computed(() => props.modelValue[1]);

function pct(value: number): number {
    return ((value - props.min) / (props.max - props.min)) * 100;
}

const rangeLabel = computed(
    () => `${props.labelPrefix}${low.value}${props.labelSuffix} – ${props.labelPrefix}${high.value}${props.labelSuffix}`,
);
const labelLeftPct = computed(() => {
    const center = (pct(low.value) + pct(high.value)) / 2;
    return Math.min(88, Math.max(12, center));
});

function onLowInput(event: Event) {
    const value = Number((event.target as HTMLInputElement).value);
    emit('update:modelValue', [Math.min(value, high.value - props.step), high.value]);
}

function onHighInput(event: Event) {
    const value = Number((event.target as HTMLInputElement).value);
    emit('update:modelValue', [low.value, Math.max(value, low.value + props.step)]);
}
</script>

<template>
    <div :class="cn('pt-1 pb-0.5', props.class)">
        <div class="relative h-5">
            <!-- Track -->
            <div
                class="absolute inset-x-0 top-[9px] h-1 rounded-full border border-border bg-sunken"
            />
            <!-- Active range -->
            <div
                class="absolute top-[9px] h-1 rounded-full bg-accent"
                :style="{ left: `${pct(low)}%`, right: `${100 - pct(high)}%` }"
            />
            <!-- Handles -->
            <div
                v-for="value in [low, high]"
                :key="value"
                class="absolute top-[3px] size-4 rounded-full border-2 border-accent bg-surface shadow-[0_1px_3px_rgba(15,23,42,0.1)]"
                :style="{ left: `calc(${pct(value)}% - 8px)` }"
            />
            <!-- Inputs overlaid for actual interaction/accessibility; only the thumb is clickable -->
            <input
                type="range"
                :min="min"
                :max="max"
                :step="step"
                :value="low"
                aria-label="Minimum"
                class="pointer-events-none absolute inset-0 m-0 h-full w-full appearance-none bg-transparent [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:size-4 [&::-moz-range-thumb]:cursor-grab [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:border-0 [&::-moz-range-thumb]:bg-transparent [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:size-4 [&::-webkit-slider-thumb]:cursor-grab [&::-webkit-slider-thumb]:appearance-none"
                @input="onLowInput"
            />
            <input
                type="range"
                :min="min"
                :max="max"
                :step="step"
                :value="high"
                aria-label="Maximum"
                class="pointer-events-none absolute inset-0 m-0 h-full w-full appearance-none bg-transparent [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:size-4 [&::-moz-range-thumb]:cursor-grab [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:border-0 [&::-moz-range-thumb]:bg-transparent [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:size-4 [&::-webkit-slider-thumb]:cursor-grab [&::-webkit-slider-thumb]:appearance-none"
                @input="onHighInput"
            />
        </div>
        <div class="relative mt-2 flex justify-between font-mono text-[11px] text-tertiary">
            <span>{{ min }}</span>
            <span class="absolute -translate-x-1/2 whitespace-nowrap" :style="{ left: `${labelLeftPct}%` }">{{ rangeLabel }}</span>
            <span>{{ max }}</span>
        </div>
    </div>
</template>
