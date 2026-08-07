import { Upload, UserPlus } from '@lucide/vue';
import type { Component } from 'vue';
import { index as documentsIndex } from '@/routes/clients/documents';
import { index as organizationMembersIndex } from '@/routes/organization-members';
import type { DocumentsUploadBatchProcessedData } from '@/types/notification';

export type NotificationTypeMeta = {
    icon: Component;
    label: string;
    resolveUrl: (data: Record<string, unknown>) => string;
};

export const DOCUMENTS_UPLOADED = 'documents.uploaded';
export const MEMBER_JOINED = 'member.joined';

/**
 * Keyed off `data.action`, the semantic key set server-side in each
 * notification's envelope. The seam a future notification type plugs into
 * without touching bell/index rendering code.
 */
export const notificationTypes: Record<string, NotificationTypeMeta> = {
    [DOCUMENTS_UPLOADED]: {
        icon: Upload,
        label: 'Document upload',
        resolveUrl: (data) =>
            documentsIndex({
                client: (data as DocumentsUploadBatchProcessedData).subject
                    .slug,
            }).url,
    },
    [MEMBER_JOINED]: {
        icon: UserPlus,
        label: 'Member joined',
        resolveUrl: () => organizationMembersIndex().url,
    },
};
