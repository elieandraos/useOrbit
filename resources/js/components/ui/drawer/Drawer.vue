<script setup lang="ts">
import { XIcon } from '@lucide/vue';
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
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 bg-[rgba(15,23,42,0.32)]"
                @click="close"
            />
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-[280ms] ease-[cubic-bezier(0.32,0.72,0.21,1)]"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition-transform duration-[280ms] ease-[cubic-bezier(0.32,0.72,0.21,1)]"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <aside
                v-if="open"
                class="fixed inset-y-0 right-0 z-50 flex w-full max-w-[420px] flex-col border-l border-border bg-surface shadow-[-12px_0_40px_rgba(15,23,42,0.08)]"
            >
                <div
                    class="flex items-center gap-3 border-b border-border px-[18px] py-3.5"
                >
                    <div v-if="title || description" class="min-w-0 flex-1">
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
                    <div v-else class="flex-1" />

                    <button
                        type="button"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-secondary transition-colors hover:bg-sunken hover:text-primary"
                        @click="close"
                    >
                        <XIcon class="size-4.5" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-[18px] py-5">
                    <slot />
                </div>

                <div
                    v-if="$slots.footer"
                    class="flex items-center gap-2.5 border-t border-border bg-sunken px-[18px] py-3.5"
                >
                    <slot name="footer" />
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>