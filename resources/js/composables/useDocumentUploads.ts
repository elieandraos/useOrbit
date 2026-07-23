import { router, useHttp } from '@inertiajs/vue3';
import type { ComputedRef, Ref } from 'vue';
import { computed, reactive, ref, watch } from 'vue';
import {
    batch as finalizeBatch,
    download as downloadDocument,
} from '@/routes/documents';
import type {
    DocumentListItem,
    DocumentResource,
    DocumentRowItem,
    UploadRowItem,
} from '@/types/document';

export type UseDocumentUploadsReturn = {
    documentsById: Record<number, DocumentResource>;
    uploads: UploadRowItem[];
    hasActiveUploads: ComputedRef<boolean>;
    documentsCount: ComputedRef<number>;
    search: Ref<string>;
    searched: ComputedRef<boolean>;
    filteredDocuments: ComputedRef<DocumentRowItem[]>;
    listItems: ComputedRef<DocumentListItem[]>;
    headerCount: Ref<number>;
    syncDocuments: (documents: DocumentResource[]) => void;
    applyBatchUpdate: (
        documents: { id: number; status: DocumentResource['status'] }[],
    ) => void;
    handleFiles: (files: File[]) => Promise<void>;
    cancelUpload: (id: string) => void;
    dismissUpload: (id: string) => void;
    removeDocument: (id: number) => void;
};

type ScopeUploadState = {
    documentsById: Record<number, DocumentResource>;
    uploads: UploadRowItem[];
};

// Module-scoped (not component-scoped) so uploads and optimistic document
// state survive Inertia navigation away from the page that started them.
// Keyed by an opaque scope key so an in-flight upload for one resource can't
// leak into another resource's tab (e.g. two different clients).
const stateByScope = reactive<Record<string, ScopeUploadState>>({});
const uploadHandlesByScope = new Map<
    string,
    Map<
        string,
        ReturnType<typeof useHttp<{ file: File | null }, DocumentResource>>
    >
>();

function scopeState(scopeKey: string): ScopeUploadState {
    if (!stateByScope[scopeKey]) {
        stateByScope[scopeKey] = { documentsById: {}, uploads: [] };
    }

    return stateByScope[scopeKey];
}

function scopeUploadHandles(
    scopeKey: string,
): Map<
    string,
    ReturnType<typeof useHttp<{ file: File | null }, DocumentResource>>
> {
    if (!uploadHandlesByScope.has(scopeKey)) {
        uploadHandlesByScope.set(scopeKey, new Map());
    }

    return uploadHandlesByScope.get(scopeKey) as Map<
        string,
        ReturnType<typeof useHttp<{ file: File | null }, DocumentResource>>
    >;
}

export function useDocumentUploads(
    scopeKey: string,
    uploadUrl: string,
): UseDocumentUploadsReturn {
    const state = scopeState(scopeKey);
    const handles = scopeUploadHandles(scopeKey);

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
        const index = state.uploads.findIndex((upload) => upload.id === id);

        if (index !== -1) {
            state.uploads.splice(index, 1);
        }
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

            http.post(uploadUrl, {
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

    const hasActiveUploads = computed(() => state.uploads.length > 0);
    const documentsCount = computed(
        () => Object.keys(state.documentsById).length,
    );

    const search = ref('');
    const searched = computed(() => search.value.trim().length > 0);

    const filteredDocuments = computed<DocumentRowItem[]>(() => {
        const query = search.value.trim().toLowerCase();
        const documents = Object.values(state.documentsById).map(
            (document): DocumentRowItem => ({ kind: 'document', ...document }),
        );

        if (!query) {
            return documents;
        }

        return documents.filter((document) =>
            document.original_filename.toLowerCase().includes(query),
        );
    });

    const listItems = computed<DocumentListItem[]>(() => [
        ...state.uploads,
        ...filteredDocuments.value,
    ]);

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

    return {
        documentsById: state.documentsById,
        uploads: state.uploads,
        hasActiveUploads,
        documentsCount,
        search,
        searched,
        filteredDocuments,
        listItems,
        headerCount,
        syncDocuments,
        applyBatchUpdate,
        handleFiles,
        cancelUpload,
        dismissUpload: removeUpload,
        removeDocument,
    };
}
