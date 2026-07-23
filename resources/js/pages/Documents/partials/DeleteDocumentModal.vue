<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { destroy as documentsDestroy } from '@/routes/documents';
import type { DocumentRowItem } from './document';

const documentToDelete = defineModel<DocumentRowItem | null>({ default: null });

const emit = defineEmits<{
    deleted: [id: number];
}>();

const isOpen = computed({
    get: () => documentToDelete.value !== null,
    set: (value) => {
        if (!value) {
            documentToDelete.value = null;
        }
    },
});
</script>

<template>
    <Form
        v-if="documentToDelete"
        v-bind="documentsDestroy.form(documentToDelete.id)"
        :options="{ preserveScroll: true }"
        v-slot="{ processing }"
        @success="
            emit('deleted', documentToDelete.id);
            documentToDelete = null;
        "
    >
        <Dialog
            v-model:open="isOpen"
            title="Delete this document?"
            :description="`${documentToDelete?.original_filename} will be permanently removed from this client's records. This can't be undone.`"
        >
            <template #footer>
                <Button variant="secondary" @click="documentToDelete = null">
                    Cancel
                </Button>
                <Button
                    type="submit"
                    variant="destructive"
                    :disabled="processing"
                >
                    Delete document
                </Button>
            </template>
        </Dialog>
    </Form>
</template>
