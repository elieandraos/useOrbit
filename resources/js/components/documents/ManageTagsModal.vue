<script setup lang="ts">
import { router, useHttp } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useTagCatalog } from '@/composables/useTagCatalog';
import {
    destroy as destroyTag,
    index as indexTags,
    update as updateTag,
} from '@/routes/tags';
import type { TagResource } from '@/types/tag';

const props = defineProps<{
    taggableType: string;
    ownerType?: string;
    reloadOnly: string[];
}>();

const open = defineModel<boolean>('open', { default: false });

const { upsertTag, removeTag } = useTagCatalog();

const tags = ref<TagResource[]>([]);
const loading = ref(false);

const editingId = ref<number | null>(null);
const editValue = ref('');
const editError = ref<string | null>(null);
const savingId = ref<number | null>(null);

const confirmId = ref<number | null>(null);
const deletingId = ref<number | null>(null);

function reloadItems(): void {
    router.reload({ only: props.reloadOnly, showProgress: false });
}

function fetchTags(): void {
    loading.value = true;

    useHttp<Record<string, never>, TagResource[]>({}).get(
        indexTags({
            query: {
                taggable_type: props.taggableType,
                owner_type: props.ownerType,
            },
        }).url,
        {
            onSuccess: (response) => {
                tags.value = response;
            },
            onFinish: () => {
                loading.value = false;
            },
        },
    );
}

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    editingId.value = null;
    confirmId.value = null;
    fetchTags();
});

function startRename(tag: TagResource): void {
    confirmId.value = null;
    editingId.value = tag.id;
    editValue.value = tag.name;
    editError.value = null;
}

function cancelRename(): void {
    editingId.value = null;
    editError.value = null;
}

function saveRename(tag: TagResource): void {
    const name = editValue.value.trim();

    if (!name || name === tag.name) {
        editingId.value = null;

        return;
    }

    savingId.value = tag.id;
    editError.value = null;

    useHttp<{ name: string }, TagResource>({ name }).patch(
        updateTag(tag.id).url,
        {
            onSuccess: (updated) => {
                tag.name = updated.name;
                upsertTag({ ...tag, name: updated.name });
                reloadItems();
                editingId.value = null;
            },
            onError: (errors) => {
                editError.value =
                    Object.values(errors)[0] ?? 'Failed to rename tag.';
            },
            onFinish: () => {
                savingId.value = null;
            },
        },
    );
}

function confirmDelete(tag: TagResource): void {
    editingId.value = null;
    confirmId.value = tag.id;
}

function cancelDelete(): void {
    confirmId.value = null;
}

function deleteTag(tag: TagResource): void {
    deletingId.value = tag.id;

    useHttp({}).delete(destroyTag(tag.id).url, {
        onSuccess: () => {
            tags.value = tags.value.filter((item) => item.id !== tag.id);
            removeTag(tag.id);
            reloadItems();
            confirmId.value = null;
        },
        onFinish: () => {
            deletingId.value = null;
        },
    });
}
</script>

<template>
    <Dialog
        v-model:open="open"
        title="Manage document tags"
        description="Renaming or deleting a tag updates it across every document."
    >
        <div class="-mx-[22px] h-[300px] overflow-y-auto">
            <div
                v-if="loading"
                class="flex h-full items-center justify-center gap-2 text-sm text-tertiary"
            >
                <Spinner class="size-4" />
                Loading tags…
            </div>

            <p
                v-else-if="tags.length === 0"
                class="flex h-full items-center justify-center px-[22px] text-sm text-tertiary"
            >
                No tags left.
            </p>

            <div
                v-for="tag in tags"
                :key="tag.id"
                class="border-b border-border-subtle last:border-b-0"
            >
                <div class="flex items-center gap-2.5 px-[22px] py-2.5">
                    <template v-if="editingId === tag.id">
                        <Input
                            v-model="editValue"
                            size="sm"
                            class="flex-1"
                            autofocus
                            @keydown.enter="saveRename(tag)"
                            @keydown.escape="cancelRename"
                        />
                        <Button variant="ghost" size="sm" @click="cancelRename">
                            Cancel
                        </Button>
                        <Button
                            variant="primary"
                            size="sm"
                            :disabled="savingId === tag.id"
                            @click="saveRename(tag)"
                        >
                            Save
                        </Button>
                    </template>

                    <template v-else-if="confirmId === tag.id">
                        <span class="flex-1 text-[12.5px] text-danger">
                            Delete "{{ tag.name }}" from
                            {{ tag.usage_count ?? 0 }}
                            document{{ tag.usage_count === 1 ? '' : 's' }}?
                        </span>
                        <Button variant="ghost" size="sm" @click="cancelDelete">
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            :disabled="deletingId === tag.id"
                            @click="deleteTag(tag)"
                        >
                            Delete
                        </Button>
                    </template>

                    <template v-else>
                        <span
                            class="size-1.5 shrink-0 rounded-full bg-accent"
                        />
                        <span class="flex flex-1 items-center gap-2 truncate">
                            <span
                                class="truncate text-[13px] font-medium text-primary"
                                >{{ tag.name }}</span
                            >
                            <Badge>{{ tag.usage_count ?? 0 }}</Badge>
                        </span>
                        <button
                            type="button"
                            title="Rename"
                            class="cursor-pointer p-1 text-tertiary hover:text-primary"
                            @click="startRename(tag)"
                        >
                            <Pencil class="size-3.5" />
                        </button>
                        <button
                            type="button"
                            title="Delete"
                            class="cursor-pointer p-1 text-tertiary hover:text-danger"
                            @click="confirmDelete(tag)"
                        >
                            <Trash2 class="size-3.5" />
                        </button>
                    </template>
                </div>

                <p
                    v-if="editError && editingId === tag.id"
                    class="px-[22px] pb-2 text-[11.5px] text-danger"
                >
                    {{ editError }}
                </p>
            </div>
        </div>

        <template #footer>
            <Button variant="secondary" size="sm" @click="open = false">
                Close
            </Button>
        </template>
    </Dialog>
</template>
