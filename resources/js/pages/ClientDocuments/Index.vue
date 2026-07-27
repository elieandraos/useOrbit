<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { SearchIcon } from '@lucide/vue';
import { onMounted, ref, watch } from 'vue';
import DeleteDocumentModal from '@/components/documents/DeleteDocumentModal.vue';
import DocumentList from '@/components/documents/DocumentList.vue';
import DocumentUploadDropzone from '@/components/documents/DocumentUploadDropzone.vue';
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
import type {
    DocumentResource,
    DocumentRowItem,
    DocumentUploadConfig,
} from '@/types/document';
import type { DocumentsUploadBatchProcessedData } from '@/types/notification';
import type { ClientResource } from '../Clients/partials/client';
import ClientDetailShell from '../Clients/partials/ClientDetailShell.vue';

const props = defineProps<{
    client: ClientResource;
    documents: DocumentResource[];
    uploadConfig: DocumentUploadConfig;
}>();

const policiesCount = 0;

const {
    hasActiveUploads,
    search,
    searched,
    listItems,
    headerCount,
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
