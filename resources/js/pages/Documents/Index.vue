<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { SearchIcon } from '@lucide/vue';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import Badge from '@/components/ui/badge/Badge.vue';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import Input from '@/components/ui/input/Input.vue';
import { useNotifications } from '@/composables/useNotifications';
import { DOCUMENTS_UPLOAD_BATCH_PROCESSED } from '@/lib/notificationTypes';
import { store as storeDocument } from '@/routes/clients/documents';
import {
    batch as finalizeBatch,
    download as downloadDocument,
} from '@/routes/documents';
import type { DocumentsUploadBatchProcessedData } from '@/types/notification';
import type { ClientResource } from '../Clients/partials/client';
import ClientDetailShell from '../Clients/partials/ClientDetailShell.vue';
import DeleteDocumentModal from './partials/DeleteDocumentModal.vue';
import type {
    DocumentListItem,
    DocumentResource,
    DocumentRowItem,
    DocumentUploadConfig,
    UploadRowItem,
} from './partials/document';
import DocumentList from './partials/DocumentList.vue';
import DocumentUploadDropzone from './partials/DocumentUploadDropzone.vue';

const props = defineProps<{
    client: ClientResource;
    documents: DocumentResource[];
    uploadConfig: DocumentUploadConfig;
}>();

const policiesCount = 0;

const documentsById = reactive<Record<number, DocumentResource>>({});

watch(
    () => props.documents,
    (documents) => {
        Object.keys(documentsById).forEach((id) => {
            delete documentsById[Number(id)];
        });

        documents.forEach((document) => {
            documentsById[document.id] = document;
        });
    },
    { immediate: true },
);

const documentsCount = computed(() => Object.keys(documentsById).length);

// A stale history-cached visit (browser back/forward) or a batch that
// finished while this page wasn't mounted (missing the live push) can both
// leave "pending" rows showing outdated state — reconcile once on mount.
onMounted(() => {
    const hasPending = props.documents.some(
        (document) => document.status === 'pending',
    );

    if (hasPending) {
        router.reload({ only: ['documents'], showProgress: false });
    }
});

const search = ref('');
const searched = computed(() => search.value.trim().length > 0);

const filteredDocuments = computed<DocumentRowItem[]>(() => {
    const query = search.value.trim().toLowerCase();
    const documents = Object.values(documentsById).map(
        (document): DocumentRowItem => ({ kind: 'document', ...document }),
    );

    if (!query) {
        return documents;
    }

    return documents.filter((document) =>
        document.original_filename.toLowerCase().includes(query),
    );
});

const { items: notifications } = useNotifications();

watch(
    () => notifications.length,
    (length, previousLength) => {
        if (length <= previousLength) {
            return;
        }

        const notification = notifications[0];

        if (notification.type !== DOCUMENTS_UPLOAD_BATCH_PROCESSED) {
            return;
        }

        const data = notification.data as DocumentsUploadBatchProcessedData;

        if (data.client.slug !== props.client.slug) {
            return;
        }

        data.documents.forEach(({ id, status }) => {
            const document = documentsById[id];

            if (!document) {
                return;
            }

            document.status = status;
            document.download_url =
                status === 'completed' ? downloadDocument(id).url : null;
            // DocumentsUploadBatchProcessed only notifies the uploader, so once a
            // document leaves "pending" every DocumentPolicy::delete condition holds.
            document.can_delete = true;
        });
    },
);

const uploads = ref<UploadRowItem[]>([]);
const uploadHandles = new Map<
    string,
    ReturnType<typeof useHttp<{ file: File | null }, DocumentResource>>
>();

const listItems = computed<DocumentListItem[]>(() => [
    ...uploads.value,
    ...filteredDocuments.value,
]);

const hasActiveUploads = computed(() => uploads.value.length > 0);

// Documents settle into documentsById one-by-one as each upload finishes, so
// documentsCount ticks up mid-batch. The header badge should instead hold at
// its pre-batch value and jump straight to the final count once every file
// in the batch has settled (succeeded or failed).
const headerCount = ref(documentsCount.value);

watch(documentsCount, (value) => {
    if (!hasActiveUploads.value) {
        headerCount.value = value;
    }
});

watch(hasActiveUploads, (isActive, wasActive) => {
    if (wasActive && !isActive) {
        headerCount.value = documentsCount.value;
    }
});

const documentToDelete = ref<DocumentRowItem | null>(null);

function stageFile(item: UploadRowItem, file: File): Promise<number | null> {
    return new Promise((resolve) => {
        const http = useHttp<{ file: File | null }, DocumentResource>({
            file: null,
        });
        http.file = file;
        uploadHandles.set(item.id, http);

        http.post(storeDocument(props.client.slug).url, {
            onProgress: (progress) => {
                item.progress = progress?.percentage ?? item.progress;
            },
            onSuccess: (response) => {
                documentsById[response.id] = response;
                removeUpload(item.id);
                resolve(response.id);
            },
            onError: (errors) => {
                item.status = 'error';
                item.errorMessage =
                    Object.values(errors)[0] ?? 'Upload failed.';
                resolve(null);
            },
            onCancel: () => {
                removeUpload(item.id);
                resolve(null);
            },
            onFinish: () => {
                uploadHandles.delete(item.id);
            },
        }).catch(() => {
            // onCancel/onError already resolved this stageFile promise;
            // useHttp rethrows after those callbacks, so swallow it here to
            // avoid an unhandled promise rejection.
        });
    });
}

async function handleFiles(files: File[]): Promise<void> {
    const items: UploadRowItem[] = files.map((file) =>
        reactive<UploadRowItem>({
            kind: 'upload',
            id: crypto.randomUUID(),
            name: file.name,
            progress: 0,
            status: 'uploading',
        }),
    );

    uploads.value.push(...items);

    const results = await Promise.allSettled(
        items.map((item, index) => stageFile(item, files[index])),
    );

    const stagedIds = results
        .filter((result) => result.status === 'fulfilled')
        .map((result) => result.value)
        .filter((id): id is number => id !== null);

    if (stagedIds.length > 0) {
        router.post(
            finalizeBatch().url,
            { document_ids: stagedIds },
            { preserveScroll: true, showProgress: false },
        );
    }
}

function removeUpload(id: string): void {
    uploads.value = uploads.value.filter((upload) => upload.id !== id);
}

function cancelUpload(id: string): void {
    uploadHandles.get(id)?.cancel();
}
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

            <DocumentUploadDropzone
                :config="uploadConfig"
                @files="handleFiles"
            />

            <CardContent class="p-0">
                <DocumentList
                    :items="listItems"
                    :searched="searched"
                    :has-active-uploads="hasActiveUploads"
                    @cancel="cancelUpload"
                    @dismiss="removeUpload"
                    @delete="documentToDelete = $event"
                />
            </CardContent>
        </Card>

        <DeleteDocumentModal v-model="documentToDelete" />
    </ClientDetailShell>
</template>
