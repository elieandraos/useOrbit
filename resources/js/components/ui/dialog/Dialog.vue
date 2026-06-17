<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue';

defineProps<{
    title?: string;
    description?: string;
}>();

const open = defineModel<boolean>('open', { default: false });

function close() {
    open.value = false;
}

function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape' && open.value) {
        close();
    }
}

onMounted(() => document.addEventListener('keydown', handleKeydown));
onBeforeUnmount(() =>
    document.removeEventListener('keydown', handleKeydown),
);
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[rgba(15,23,42,0.32)] p-6"
        @click.self="close"
    >
        <div
            class="w-full max-w-[460px] overflow-hidden rounded-[12px] border border-border bg-surface shadow-2xl"
        >
            <div
                v-if="title || description"
                class="px-[22px] pt-[18px] pb-[14px]"
            >
                <h2
                    v-if="title"
                    class="text-lg font-semibold text-primary"
                >
                    {{ title }}
                </h2>
                <p
                    v-if="description"
                    class="mt-1 text-sm leading-[1.55] text-secondary"
                >
                    {{ description }}
                </p>
            </div>

            <div class="px-[22px] pb-[18px]">
                <slot />
            </div>

            <div
                v-if="$slots.footer"
                class="flex justify-end gap-2 border-t border-border bg-sunken px-[22px] py-[14px]"
            >
                <slot name="footer" />
            </div>
        </div>
    </div>
</template>
