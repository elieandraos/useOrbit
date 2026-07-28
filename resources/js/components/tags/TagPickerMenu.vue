<script setup lang="ts">
import { Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import type { TagResource } from '@/types/tag';

const props = defineProps<{
    availableTags: TagResource[];
    attachedTagIds: number[];
}>();

const emit = defineEmits<{
    toggle: [tagId: number];
    create: [name: string];
}>();

const query = ref('');

const normalizedQuery = computed(() => query.value.trim().toLowerCase());

const filteredTags = computed(() => {
    if (!normalizedQuery.value) {
        return props.availableTags;
    }

    return props.availableTags.filter((tag) =>
        tag.name.toLowerCase().includes(normalizedQuery.value),
    );
});

const showCreateRow = computed(() => {
    if (!normalizedQuery.value) {
        return false;
    }

    return !props.availableTags.some(
        (tag) => tag.name.toLowerCase() === normalizedQuery.value,
    );
});

function isAttached(tagId: number): boolean {
    return props.attachedTagIds.includes(tagId);
}

function handleCreate(): void {
    const name = query.value.trim();

    if (!name) {
        return;
    }

    emit('create', name);
    query.value = '';
}
</script>

<template>
    <div class="w-60">
        <div class="px-1 pb-1.5">
            <Input v-model="query" size="sm" placeholder="Find or create…">
                <template #leading><Search /></template>
            </Input>
        </div>

        <label
            v-for="tag in filteredTags"
            :key="tag.id"
            class="flex cursor-pointer items-center gap-2 rounded-[6px] px-2 py-1.5 text-sm text-primary hover:bg-sunken"
            @click.stop
        >
            <Checkbox
                :model-value="isAttached(tag.id)"
                @update:model-value="emit('toggle', tag.id)"
            />
            <span class="flex-1 truncate">{{ tag.name }}</span>
            <span class="font-mono text-[10.5px] text-tertiary">{{
                tag.usage_count ?? 0
            }}</span>
        </label>

        <p
            v-if="filteredTags.length === 0 && !showCreateRow"
            class="px-2 py-1.5 text-sm text-tertiary"
        >
            No matching tags
        </p>

        <button
            v-if="showCreateRow"
            type="button"
            class="flex w-full items-center gap-1 rounded-[6px] px-2 py-1.5 text-left text-sm text-secondary hover:bg-sunken"
            @click.stop="handleCreate"
        >
            <span class="text-tertiary">Create</span>
            <span class="truncate">"{{ query.trim() }}"</span>
        </button>
    </div>
</template>
