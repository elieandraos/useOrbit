import { Upload } from '@lucide/vue';
import type { Component } from 'vue';
import { index as documentsIndex } from '@/routes/clients/documents';
import type { DocumentsUploadBatchProcessedData } from '@/types/notification';

export type NotificationTypeMeta = {
    icon: Component;
    label: string;
    resolveUrl: (data: Record<string, unknown>) => string;
};

export const DOCUMENTS_UPLOAD_BATCH_PROCESSED =
    'App\\Notifications\\DocumentsUploadBatchProcessed';

/**
 * Keyed off the raw FQCN stored in the `notifications` table's `type` column.
 * The seam a future notification type plugs into without touching bell/index rendering code.
 */
export const notificationTypes: Record<string, NotificationTypeMeta> = {
    [DOCUMENTS_UPLOAD_BATCH_PROCESSED]: {
        icon: Upload,
        label: 'Document upload',
        resolveUrl: (data) =>
            documentsIndex({
                client: (data as DocumentsUploadBatchProcessedData).client.slug,
            }).url,
    },
};
