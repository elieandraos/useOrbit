<script setup lang="ts">
import { Pencil, Pin, Trash2 } from '@lucide/vue';
import { Avatar } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { NoteResource } from '@/types/note';

defineProps<{
    note: NoteResource;
    flash?: boolean;
}>();

const emit = defineEmits<{
    edit: [note: NoteResource];
    delete: [note: NoteResource];
}>();
</script>

<template>
    <div
        :class="
            cn(
                'rounded-lg border p-[18px] shadow-sm',
                note.pinned
                    ? 'border-warning-bg bg-warning-bg/40'
                    : 'border-border bg-surface',
                flash && 'animate-flash-highlight',
            )
        "
    >
        <div class="flex items-start gap-3">
            <Avatar :name="note.created_by_name ?? ''" :size="32" />
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <span class="text-[13.5px] font-semibold text-primary">
                        {{ note.created_by_name }}
                    </span>
                    <span class="font-mono text-[11.5px] text-tertiary">
                        {{ note.created_at }}
                    </span>
                    <Badge v-if="note.pinned" tone="warning">
                        <Pin class="size-2.5" />
                        Pinned
                    </Badge>
                </div>
                <p
                    class="mt-2 text-[13.5px] leading-[1.55] whitespace-pre-wrap text-primary"
                >
                    {{ note.body }}
                </p>
                <div class="mt-2.5 flex items-center gap-1">
                    <div class="flex-1" />
                    <button
                        v-if="note.can_update"
                        type="button"
                        title="Edit"
                        class="shrink-0 cursor-pointer p-1 text-tertiary hover:text-primary"
                        @click="emit('edit', note)"
                    >
                        <Pencil class="size-3.5" />
                    </button>
                    <button
                        v-if="note.can_delete"
                        type="button"
                        title="Delete"
                        class="shrink-0 cursor-pointer p-1 text-tertiary hover:text-danger"
                        @click="emit('delete', note)"
                    >
                        <Trash2 class="size-3.5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
