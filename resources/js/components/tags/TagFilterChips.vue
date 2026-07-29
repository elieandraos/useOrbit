<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { TagResource } from '@/types/tag';

defineProps<{
    tags: TagResource[];
    totalCount: number;
    selectedTagId: number | null;
}>();

const emit = defineEmits<{
    select: [tagId: number | null];
}>();
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <button
            type="button"
            :class="
                cn(
                    'inline-flex h-[30px] cursor-pointer items-center gap-1.5 rounded-full border px-3 text-[12.5px] font-medium',
                    selectedTagId === null
                        ? 'border-accent bg-accent-bg text-accent'
                        : 'border-border bg-surface text-secondary hover:text-primary',
                )
            "
            @click="emit('select', null)"
        >
            All
            <span
                class="font-mono text-[11px]"
                :class="
                    selectedTagId === null ? 'text-accent' : 'text-tertiary'
                "
                >{{ totalCount }}</span
            >
        </button>

        <button
            v-for="tag in tags"
            :key="tag.id"
            type="button"
            :class="
                cn(
                    'inline-flex h-[30px] cursor-pointer items-center gap-1.5 rounded-full border px-3 text-[12.5px] font-medium',
                    selectedTagId === tag.id
                        ? 'border-accent bg-accent-bg text-accent'
                        : 'border-border bg-surface text-secondary hover:text-primary',
                )
            "
            @click="emit('select', tag.id)"
        >
            {{ tag.name }}
            <span
                class="font-mono text-[11px]"
                :class="
                    selectedTagId === tag.id ? 'text-accent' : 'text-tertiary'
                "
                >{{ tag.usage_count ?? 0 }}</span
            >
        </button>
    </div>
</template>
