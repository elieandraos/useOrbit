<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { SearchIcon } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
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
import { useNotifications } from '@/composables/useNotifications';
import { DOCUMENTS_UPLOADED } from '@/lib/notificationTypes';
import { store as storeDocument } from '@/routes/clients/documents';
import type { DocumentsUploadBatchProcessedData } from '@/types/notification';
import type { ClientResource } from '../Clients/partials/client';
import ClientDetailShell from '../Clients/partials/ClientDetailShell.vue';
import DeleteDocumentModal from './partials/DeleteDocumentModal.vue';
import type {
    DocumentListItem,
    DocumentResource,
    DocumentRowItem,
    DocumentUploadConfig,
} from './partials/document';
import DocumentList from './partials/DocumentList.vue';
import DocumentUploadDropzone from './partials/DocumentUploadDropzone.vue';

const props = defineProps<{
    client: ClientResource;
    documents: DocumentResource[];
    uploadConfig: DocumentUploadConfig;
}>();

const policiesCount = 0;

const {
    documentsById,
    uploads,
    hasActiveUploads,
    syncDocuments,
    applyBatchUpdate,
    handleFiles,
    cancelUpload,
    dismissUpload,
    removeDocument,
} = useDocumentUploads(
    `client-${props.client.id}`,
    storeDocument(props.client.slug).url,
);

watch(() => props.documents, syncDocuments, { immediate: true });

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

const listItems = computed<DocumentListItem[]>(() => [
    ...uploads,
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

const documentToDelete = ref<DocumentRowItem | null>(null);
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
                    @dismiss="dismissUpload"
                    @delete="documentToDelete = $event"
                />
            </CardContent>
        </Card>

        <DeleteDocumentModal
            v-model="documentToDelete"
            @deleted="removeDocument"
        />
    </ClientDetailShell>
</template>
