<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Avatar } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import NoteCharacterCounter from './NoteCharacterCounter.vue';

const props = defineProps<{
    maxLength: number;
    processing?: boolean;
}>();

const emit = defineEmits<{
    submit: [body: string];
}>();

const page = usePage();
const authorName = computed(() => page.props.auth.user.name);

const body = ref('');

const isOverLimit = computed(() => body.value.length > props.maxLength);
const canSave = computed(
    () =>
        body.value.trim().length > 0 && !isOverLimit.value && !props.processing,
);

function submit(): void {
    if (!canSave.value) {
        return;
    }

    emit('submit', body.value.trim());
    body.value = '';
}

function cancel(): void {
    body.value = '';
}
</script>

<template>
    <div class="flex items-start gap-3">
        <div class="flex flex-col items-center gap-1.5">
            <Avatar :name="authorName" :size="32" />
            <NoteCharacterCounter :length="body.length" :max="maxLength" />
        </div>
        <div class="min-w-0 flex-1">
            <Textarea
                v-model="body"
                :disabled="processing"
                rows="3"
                placeholder="Add a note — call summary, follow-up, etc."
                class="w-full"
            />
            <div class="mt-2 flex items-center gap-2">
                <div class="flex-1" />
                <Button
                    v-if="body.trim().length > 0"
                    variant="ghost"
                    size="sm"
                    :disabled="processing"
                    @click="cancel"
                >
                    Cancel
                </Button>
                <Button
                    variant="primary"
                    size="sm"
                    :disabled="!canSave"
                    @click="submit"
                >
                    Save note
                </Button>
            </div>
        </div>
    </div>
</template>
