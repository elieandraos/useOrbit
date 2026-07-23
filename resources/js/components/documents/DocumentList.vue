<script setup lang="ts">
import { FileX2 } from '@lucide/vue';
import type { DocumentListItem, DocumentRowItem } from '@/types/document';
import DocumentRow from './DocumentRow.vue';

defineProps<{
    items: DocumentListItem[];
    searched: boolean;
    hasActiveUploads: boolean;
}>();

const emit = defineEmits<{
    cancel: [id: string];
    dismiss: [id: string];
    delete: [document: DocumentRowItem];
}>();
</script>

<template>
    <div class="flex flex-col gap-2 px-[18px] pb-[18px]">
        <DocumentRow
            v-for="item in items"
            :key="item.kind === 'upload' ? item.id : `document-${item.id}`"
            :item="item"
            :has-active-uploads="hasActiveUploads"
            @cancel="emit('cancel', $event)"
            @dismiss="emit('dismiss', $event)"
            @delete="emit('delete', $event)"
        />

        <div
            v-if="items.length === 0"
            class="flex flex-col items-center gap-2 py-10 text-center"
        >
            <FileX2 class="size-6 text-tertiary" />
            <p class="text-sm text-secondary">
                {{
                    searched
                        ? 'No documents match your search.'
                        : 'No documents yet — drag files above to upload.'
                }}
            </p>
        </div>
    </div>
</template>
