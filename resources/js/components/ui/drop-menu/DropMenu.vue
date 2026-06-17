<script setup lang="ts">
import { onBeforeUnmount, onMounted, provide, ref } from 'vue';

withDefaults(
    defineProps<{
        align?: 'start' | 'end';
    }>(),
    { align: 'end' },
);

const menuOpen = ref(false);
const menuRef = ref<HTMLElement | null>(null);

function close() {
    menuOpen.value = false;
}

function toggle() {
    menuOpen.value = !menuOpen.value;
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
        <div @click="toggle">
            <slot name="trigger" />
        </div>

        <div
            v-if="menuOpen"
            class="absolute top-[calc(100%+6px)] z-20 min-w-[220px] rounded-[10px] border border-border bg-surface p-1 shadow-lg"
            :class="align === 'start' ? 'left-0' : 'right-0'"
        >
            <slot />
        </div>
    </div>
</template>
