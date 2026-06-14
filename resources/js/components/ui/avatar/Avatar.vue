<script setup lang="ts">
import { computed, ref } from 'vue';
import { getInitials } from '@/composables/useInitials';

const props = withDefaults(
    defineProps<{
        name: string;
        src?: string | null;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    { size: 'md' },
);

const imageError = ref(false);

const showImage = computed(() => !!props.src && !imageError.value);

const initials = computed(() => getInitials(props.name));

const sizeClasses: Record<string, string> = {
    sm: 'size-6 text-[10px]',
    md: 'size-8 text-xs',
    lg: 'size-10 text-sm',
};

function nameToHue(name: string): number {
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash += name.charCodeAt(i);
    }
    return hash % 360;
}

const fallbackStyle = computed(() => ({
    backgroundColor: `hsl(${nameToHue(props.name)}, 55%, 45%)`,
    color: '#fff',
}));
</script>

<template>
    <div
        data-slot="avatar"
        class="relative shrink-0 overflow-hidden rounded-full"
        :class="sizeClasses[size]"
    >
        <img
            v-if="showImage"
            :src="src!"
            :alt="name"
            class="size-full object-cover"
            @error="imageError = true"
        />
        <div
            v-else
            class="flex size-full items-center justify-center font-medium"
            :style="fallbackStyle"
        >
            {{ initials }}
        </div>
    </div>
</template>
