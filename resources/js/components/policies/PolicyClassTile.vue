<script setup lang="ts">
import { computed } from 'vue';
import type { PolicyResource } from '@/types/policy';

const props = withDefaults(
    defineProps<{
        policy: Pick<PolicyResource, 'class' | 'class_label'>;
        size?: 'md' | 'lg' | 'xl';
    }>(),
    { size: 'md' },
);

const classColors: Record<string, string> = {
    medical: '#0369a1',
    automotive: '#b45309',
    expat: '#7c3aed',
    life: '#15803d',
    fire: '#b91c1c',
    travel: '#0d9488',
};

const sizeClasses: Record<string, string> = {
    md: 'size-9 rounded-lg text-[10.5px]',
    lg: 'size-16 rounded-xl text-[17px]',
    xl: 'size-[72px] rounded-2xl text-[19px]',
};

const classColor = computed(() => classColors[props.policy.class] ?? '#52525b');

const tileStyle = computed(() => ({
    backgroundColor: `${classColor.value}15`,
    borderColor: `${classColor.value}30`,
    color: classColor.value,
}));
</script>

<template>
    <div
        data-slot="policy-class-tile"
        class="flex shrink-0 items-center justify-center border font-mono font-bold tracking-wider uppercase"
        :class="sizeClasses[size]"
        :style="tileStyle"
    >
        {{ policy.class_label.slice(0, 3) }}
    </div>
</template>
