<script setup lang="ts">
import { Pencil } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Avatar } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import type { NoteResource } from '@/types/note';
import NoteCharacterCounter from './NoteCharacterCounter.vue';

const props = defineProps<{
    note: NoteResource;
    maxLength: number;
    processing?: boolean;
}>();

const emit = defineEmits<{
    save: [payload: { body: string; pinned: boolean }];
    cancel: [];
}>();

const body = ref(props.note.body);
const pinned = ref(props.note.pinned);

const isOverLimit = computed(() => body.value.length > props.maxLength);
const canSave = computed(
    () =>
        body.value.trim().length > 0 && !isOverLimit.value && !props.processing,
);

function save(): void {
    if (!canSave.value) {
        return;
    }

    emit('save', { body: body.value.trim(), pinned: pinned.value });
}
</script>

<template>
    <div class="rounded-lg border-2 border-accent bg-surface p-[18px]">
        <div class="flex items-start gap-3">
            <div class="flex flex-col items-center gap-1.5">
                <Avatar :name="note.created_by_name ?? ''" :size="32" />
                <NoteCharacterCounter :length="body.length" :max="maxLength" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="mb-2.5 flex items-center gap-2">
                    <span class="text-[13.5px] font-semibold text-primary">
                        {{ note.created_by_name }}
                    </span>
                    <span class="font-mono text-[11.5px] text-tertiary">
                        {{ note.created_at }}
                    </span>
                    <Badge tone="accent">
                        <Pencil class="size-2.5" />
                        Editing
                    </Badge>
                </div>

                <Textarea
                    v-model="body"
                    :disabled="processing"
                    rows="4"
                    class="w-full"
                />

                <div class="mt-3 flex items-center gap-3">
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 text-[12.5px] text-secondary"
                    >
                        <Checkbox v-model="pinned" :disabled="processing" />
                        Pin to top
                    </label>
                    <div class="flex-1" />
                    <Button
                        variant="ghost"
                        size="sm"
                        :disabled="processing"
                        @click="emit('cancel')"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="primary"
                        size="sm"
                        :disabled="!canSave"
                        @click="save"
                    >
                        Save changes
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
