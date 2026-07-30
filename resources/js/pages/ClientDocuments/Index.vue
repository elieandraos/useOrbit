<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { SearchIcon } from '@lucide/vue';
import { onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import DeleteDocumentModal from '@/components/documents/DeleteDocumentModal.vue';
import DocumentList from '@/components/documents/DocumentList.vue';
import DocumentUploadDropzone from '@/components/documents/DocumentUploadDropzone.vue';
import ManageTagsModal from '@/components/documents/ManageTagsModal.vue';
import TagFilterChips from '@/components/tags/TagFilterChips.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import Input from '@/components/ui/input/Input.vue';
import { useDocumentUploads } from '@/composables/useDocumentUploads';
import { useFlashHighlight } from '@/composables/useFlashHighlight';
import { useNotifications } from '@/composables/useNotifications';
import { useTagCatalog } from '@/composables/useTagCatalog';
import { DOCUMENTS_UPLOADED } from '@/lib/notificationTypes';
import { store as storeDocument } from '@/routes/clients/documents';
import type {
    DocumentResource,
    DocumentRowItem,
    DocumentUploadConfig,
} from '@/types/document';
import type { DocumentsUploadBatchProcessedData } from '@/types/notification';
import type { TagResource } from '@/types/tag';
import type { ClientResource } from '../Clients/partials/client';
import ClientDetailShell from '../Clients/partials/ClientDetailShell.vue';

const props = defineProps<{
    client: ClientResource;
    documents: DocumentResource[];
    tags: TagResource[];
    uploadConfig: DocumentUploadConfig;
}>();

const policiesCount = 0;

const { flash, isFlashing } = useFlashHighlight();

const {
    documentsById,
    hasActiveUploads,
    search,
    searched,
    activeTagId,
    listItems,
    headerCount,
    documentsCount,
    syncDocuments,
    applyBatchUpdate,
    handleFiles,
    cancelUpload,
    dismissUpload,
    removeDocument,
    attachTag,
    detachTag,
} = useDocumentUploads(
    `client-${props.client.id}`,
    storeDocument(props.client.slug).url,
    flash,
);

const { tags: tagCatalog, syncTags, createTag } = useTagCatalog();

watch(() => props.documents, syncDocuments, { immediate: true });
watch(() => props.tags, syncTags, { immediate: true });

async function handleToggleTag(
    documentId: number,
    tagId: number,
): Promise<void> {
    const document = documentsById[documentId];

    if (!document) {
        return;
    }

    const isAttached = document.tags.some((tag) => tag.id === tagId);

    try {
        if (isAttached) {
            await detachTag(documentId, tagId);
        } else {
            await attachTag(documentId, tagId);
        }
    } catch {
        toast.error(
            isAttached ? 'Failed to remove tag.' : 'Failed to attach tag.',
        );
    }
}

async function handleCreateTag(
    documentId: number,
    name: string,
): Promise<void> {
    try {
        const tag = await createTag(name);
        await attachTag(documentId, tag.id);
    } catch {
        toast.error('Failed to create tag.');
    }
}

// A stale history-cached visit (browser back/forward) or a batch that
// finished while this page wasn't mounted (missing the live push) can both
// leave "pending" rows showing outdated state — reconcile once on mount.
onMounted(() => {
    const hasPending = props.documents.some(
        (document) =>
            document.status === 'pending' || document.status === 'processing',
    );

    if (hasPending) {
        router.reload({ only: ['documents'], showProgress: false });
    }
});

const { items: notifications } = useNotifications();

watch(
    () => notifications.length,
    (length, previousLength) => {
        if (length <= previousLength) {
            return;
        }

        const notification = notifications[0];
        const data = notification.data as DocumentsUploadBatchProcessedData;

        if (data.action !== DOCUMENTS_UPLOADED) {
            return;
        }

        if (data.subject.slug !== props.client.slug) {
            return;
        }

        applyBatchUpdate(data.meta.documents);
    },
);

const documentToDelete = ref<DocumentRowItem | null>(null);
const manageTagsOpen = ref(false);
</script>

<template>
    <ClientDetailShell :client="client" :policies-count="policiesCount">
        <Head :title="`${client.full_name} · Documents`" />

        <Card class="mt-6">
            <CardHeader bordered>
                <CardTitle>Documents</CardTitle>
                <Badge v-if="headerCount > 0" tone="accent">{{
                    headerCount
                }}</Badge>
                <CardAction>
                    <Input
                        v-model="search"
                        size="sm"
                        placeholder="Search documents…"
                        class="w-60"
                    >
                        <template #leading><SearchIcon /></template>
                    </Input>
                </CardAction>
            </CardHeader>

            <div
                v-if="documentsCount > 0 && tagCatalog.length > 0"
                class="flex items-center justify-between gap-3 border-b border-border-subtle bg-sunken px-6 py-3"
            >
                <TagFilterChips
                    :tags="tagCatalog"
                    :total-count="documentsCount"
                    :selected-tag-id="activeTagId"
                    @select="activeTagId = $event"
                />
                <button
                    type="button"
                    class="shrink-0 cursor-pointer text-[12.5px] font-medium text-tertiary hover:text-primary"
                    @click="manageTagsOpen = true"
                >
                    Manage tags
                </button>
            </div>

            <DocumentUploadDropzone
                :config="uploadConfig"
                @files="handleFiles"
            />

            <CardContent class="min-h-[488px] p-0">
                <DocumentList
                    :items="listItems"
                    :searched="searched"
                    :has-active-uploads="hasActiveUploads"
                    :available-tags="tagCatalog"
                    :is-flashing="isFlashing"
                    @cancel="cancelUpload"
                    @dismiss="dismissUpload"
                    @toggle-tag="handleToggleTag"
                    @create-tag="handleCreateTag"
                    @delete="documentToDelete = $event"
                />
            </CardContent>
        </Card>

        <DeleteDocumentModal
            v-model="documentToDelete"
            @deleted="removeDocument"
        />

        <ManageTagsModal
            v-model:open="manageTagsOpen"
            taggable-type="documents"
            owner-type="clients"
            :owner-id="client.id"
            :reload-only="['documents']"
        />
    </ClientDetailShell>
</template>
