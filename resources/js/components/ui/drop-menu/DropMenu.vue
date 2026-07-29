<script lang="ts">
let activeClose: (() => void) | null = null;
</script>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { onBeforeUnmount, onMounted, provide, ref } from 'vue';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        align?: 'start' | 'end';
        side?: 'top' | 'bottom';
        panelClass?: HTMLAttributes['class'];
    }>(),
    { align: 'end', side: 'bottom' },
);

const emit = defineEmits<{
    close: [];
}>();

const menuOpen = ref(false);
const menuRef = ref<HTMLElement | null>(null);

function close() {
    if (!menuOpen.value) {
        return;
    }

    menuOpen.value = false;

    if (activeClose === close) {
        activeClose = null;
    }

    emit('close');
}

function toggle() {
    if (menuOpen.value) {
        close();
        return;
    }

    activeClose?.();
    activeClose = close;
    menuOpen.value = true;
}

function handleClickOutside(event: MouseEvent) {
    if (menuRef.value && !menuRef.value.contains(event.target as Node)) {
        close();
    }
}

provide('dropMenuClose', close);

onMounted(() => document.addEventListener('click', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <div ref="menuRef" class="relative">
        <div class="inline-flex" @click="toggle">
            <slot name="trigger" />
        </div>

        <div
            v-if="menuOpen"
            class="absolute z-20 min-w-[220px] rounded-[10px] border border-border bg-surface p-2 shadow-lg"
            :class="
                cn(
                    side === 'top'
                        ? 'bottom-[calc(100%+2px)]'
                        : 'top-[calc(100%+2px)]',
                    align === 'start' ? 'left-0' : 'right-0',
                    panelClass,
                )
            "
        >
            <slot />
        </div>
    </div>
</template>
