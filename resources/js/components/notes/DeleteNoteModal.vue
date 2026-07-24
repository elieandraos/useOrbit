<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { destroy as notesDestroy } from '@/routes/notes';
import type { NoteResource } from '@/types/note';

const noteToDelete = defineModel<NoteResource | null>({ default: null });

const emit = defineEmits<{
    deleted: [id: number];
}>();

const isOpen = computed({
    get: () => noteToDelete.value !== null,
    set: (value) => {
        if (!value) {
            noteToDelete.value = null;
        }
    },
});
</script>

<template>
    <Form
        v-if="noteToDelete"
        v-bind="notesDestroy.form(noteToDelete.id)"
        :options="{ preserveScroll: true }"
        v-slot="{ processing }"
        @success="
            emit('deleted', noteToDelete.id);
            noteToDelete = null;
        "
    >
        <Dialog
            v-model:open="isOpen"
            title="Delete this note?"
            description="This note will be permanently removed from this client's records. This can't be undone."
        >
            <template #footer>
                <Button variant="secondary" @click="noteToDelete = null">
                    Cancel
                </Button>
                <Button
                    type="submit"
                    variant="destructive"
                    :disabled="processing"
                >
                    Delete note
                </Button>
            </template>
        </Dialog>
    </Form>
</template>
