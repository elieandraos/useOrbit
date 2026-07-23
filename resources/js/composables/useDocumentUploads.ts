import { router, useHttp } from '@inertiajs/vue3';
import type { ComputedRef } from 'vue';
import { computed, reactive } from 'vue';
import type {
    DocumentResource,
    UploadRowItem,
} from '@/pages/Documents/partials/document';
import { store as storeDocument } from '@/routes/clients/documents';
import {
    batch as finalizeBatch,
    download as downloadDocument,
} from '@/routes/documents';

export type UseDocumentUploadsReturn = {
    documentsById: Record<number, DocumentResource>;
    uploads: UploadRowItem[];
    hasActiveUploads: ComputedRef<boolean>;
    syncDocuments: (documents: DocumentResource[]) => void;
    applyBatchUpdate: (
        documents: { id: number; status: DocumentResource['status'] }[],
    ) => void;
    handleFiles: (files: File[]) => Promise<void>;
    cancelUpload: (id: string) => void;
    dismissUpload: (id: string) => void;
    removeDocument: (id: number) => void;
};

type ClientUploadState = {
    documentsById: Record<number, DocumentResource>;
    uploads: UploadRowItem[];
};

// Module-scoped (not component-scoped) so uploads and optimistic document
// state survive Inertia navigation away from Documents/Index.vue instead of
// dying with the component that started them. Keyed by client slug so an
// in-flight upload for one client can't leak into another client's tab.
const stateByClient = reactive<Record<string, ClientUploadState>>({});
const uploadHandlesByClient = new Map<
    string,
    Map<
        string,
        ReturnType<typeof useHttp<{ file: File | null }, DocumentResource>>
    >
>();

function clientState(slug: string): ClientUploadState {
    if (!stateByClient[slug]) {
        stateByClient[slug] = { documentsById: {}, uploads: [] };
    }

    return stateByClient[slug];
}

function clientUploadHandles(
    slug: string,
): Map<
    string,
    ReturnType<typeof useHttp<{ file: File | null }, DocumentResource>>
> {
    if (!uploadHandlesByClient.has(slug)) {
        uploadHandlesByClient.set(slug, new Map());
    }

    return uploadHandlesByClient.get(slug) as Map<
        string,
        ReturnType<typeof useHttp<{ file: File | null }, DocumentResource>>
    >;
}

export function useDocumentUploads(
    clientSlug: string,
): UseDocumentUploadsReturn {
    const state = clientState(clientSlug);
    const handles = clientUploadHandles(clientSlug);

    function syncDocuments(documents: DocumentResource[]): void {
        documents.forEach((document) => {
            state.documentsById[document.id] = document;
        });
    }

    function applyBatchUpdate(
        documents: { id: number; status: DocumentResource['status'] }[],
    ): void {
        documents.forEach(({ id, status }) => {
            const document = state.documentsById[id];

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
    }

    function removeDocument(id: number): void {
        delete state.documentsById[id];
    }

    function removeUpload(id: string): void {
        state.uploads = state.uploads.filter((upload) => upload.id !== id);
    }

    function stageFile(
        item: UploadRowItem,
        file: File,
    ): Promise<number | null> {
        return new Promise((resolve) => {
            const http = useHttp<{ file: File | null }, DocumentResource>({
                file: null,
            });
            http.file = file;
            handles.set(item.id, http);

            http.post(storeDocument(clientSlug).url, {
                onProgress: (progress) => {
                    item.progress = progress?.percentage ?? item.progress;
                },
                onSuccess: (response) => {
                    state.documentsById[response.id] = response;
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
                    handles.delete(item.id);
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

        state.uploads.push(...items);

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

    function cancelUpload(id: string): void {
        handles.get(id)?.cancel();
    }

    return {
        documentsById: state.documentsById,
        uploads: state.uploads,
        hasActiveUploads: computed(() => state.uploads.length > 0),
        syncDocuments,
        applyBatchUpdate,
        handleFiles,
        cancelUpload,
        dismissUpload: removeUpload,
        removeDocument,
    };
}
