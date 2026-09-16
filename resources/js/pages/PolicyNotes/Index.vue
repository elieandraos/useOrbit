<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { StickyNote } from '@lucide/vue';
import { ref } from 'vue';
import DeleteNoteModal from '@/components/notes/DeleteNoteModal.vue';
import NoteCard from '@/components/notes/NoteCard.vue';
import NoteComposer from '@/components/notes/NoteComposer.vue';
import NoteEditor from '@/components/notes/NoteEditor.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useFlashHighlight } from '@/composables/useFlashHighlight';
import { update as updateNote } from '@/routes/notes';
import { store as storeNote } from '@/routes/policies/notes';
import type { NoteConfig, NoteResource } from '@/types/note';
import type { PolicyResource } from '@/types/policy';
import PolicyMedicalDetailShell from '../PolicyMedical/partials/PolicyMedicalDetailShell.vue';

const props = defineProps<{
    policy: PolicyResource;
    notes: NoteResource[];
    noteConfig: NoteConfig;
}>();

const editingNoteId = ref<number | null>(null);
const noteToDelete = ref<NoteResource | null>(null);
const creating = ref(false);
const updating = ref(false);

const { flash, isFlashing } = useFlashHighlight();

function reloadNotes(): void {
    router.reload({ only: ['notes'] });
}

function createNote(body: string): void {
    creating.value = true;

    useHttp<{ body: string }, NoteResource>({ body }).post(
        storeNote(props.policy.slug).url,
        {
            onSuccess: (note) => {
                flash(note.id);
                reloadNotes();
            },
            onFinish: () => {
                creating.value = false;
            },
        },
    );
}

function saveNote(
    note: NoteResource,
    payload: { body: string; pinned: boolean },
): void {
    updating.value = true;

    useHttp<{ body: string; pinned: boolean }, NoteResource>(payload).patch(
        updateNote(note.id).url,
        {
            onSuccess: () => {
                editingNoteId.value = null;
                reloadNotes();
            },
            onFinish: () => {
                updating.value = false;
            },
        },
    );
}
</script>

<template>
    <PolicyMedicalDetailShell :policy="policy">
        <Head :title="`${policy.policy_number} · Notes`" />

        <Card class="mt-6 min-h-[488px]">
            <CardHeader bordered>
                <CardTitle>Notes</CardTitle>
            </CardHeader>
            <CardContent class="flex flex-col gap-4">
                <NoteComposer
                    :max-length="noteConfig.max_length"
                    :processing="creating"
                    @submit="createNote"
                />

                <div class="flex flex-col gap-4">
                    <template v-if="notes.length > 0">
                        <template v-for="note in notes" :key="note.id">
                            <NoteEditor
                                v-if="note.id === editingNoteId"
                                :note="note"
                                :max-length="noteConfig.max_length"
                                :processing="updating"
                                @save="(payload) => saveNote(note, payload)"
                                @cancel="editingNoteId = null"
                            />
                            <NoteCard
                                v-else
                                :note="note"
                                :flash="isFlashing(note.id)"
                                @edit="editingNoteId = note.id"
                                @delete="noteToDelete = note"
                            />
                        </template>
                    </template>
                    <div
                        v-else
                        class="flex flex-col items-center gap-2 py-10 text-center"
                    >
                        <StickyNote class="size-6 text-tertiary" />
                        <p class="text-sm text-secondary">No notes yet.</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <DeleteNoteModal v-model="noteToDelete" />
    </PolicyMedicalDetailShell>
</template>
