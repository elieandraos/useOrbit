import {
    Archive,
    ArchiveRestore,
    MessageSquare,
    UserCog,
    UserMinus,
    UserPlus,
    Upload,
} from '@lucide/vue';
import type { Component } from 'vue';
import { show as agentShow } from '@/routes/agents';
import { show as carrierShow } from '@/routes/carriers';
import { show as clientShow } from '@/routes/clients';
import { index as documentsIndex } from '@/routes/clients/documents';
import { index as organizationMembersIndex } from '@/routes/organization-members';
import type {
    DocumentsUploadBatchProcessedData,
    NotificationSubject,
    ResourceMessageMeta,
} from '@/types/notification';

export type NotificationTypeMeta = {
    icon: Component;
    label: string;
    resolveUrl: (data: Record<string, unknown>) => string;
};

export const DOCUMENTS_UPLOADED = 'documents.uploaded';
export const MEMBER_JOINED = 'member.joined';
export const RESOURCE_ARCHIVED = 'resource.archived';
export const RESOURCE_UNARCHIVED = 'resource.unarchived';
export const MEMBER_ROLE_CHANGED = 'member.role_changed';
export const MEMBER_REMOVED = 'member.removed';
export const RESOURCE_MESSAGE = 'resource.message';

/**
 * Resolves a notification's destination from its `subject` (and, for
 * `document` subjects, `meta.parent`) — the seam every archive/role/removal/
 * manual-message action shares, since none of them need action-specific
 * routing beyond "where does this subject live".
 */
function resolveSubjectUrl(
    subject: NotificationSubject,
    meta: Record<string, unknown>,
): string {
    switch (subject.kind) {
        case 'client':
            return clientShow({ client: subject.slug }).url;
        case 'carrier':
            return carrierShow({ carrier: subject.slug }).url;
        case 'agent':
            return agentShow({ agent: subject.slug }).url;
        case 'organization':
            return organizationMembersIndex().url;
        case 'document': {
            const parent = (meta as Partial<ResourceMessageMeta>).parent;

            return parent ? documentsIndex({ client: parent.slug }).url : '#';
        }
        default:
            return '#';
    }
}

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
    [RESOURCE_ARCHIVED]: {
        icon: Archive,
        label: 'Resource archived',
        resolveUrl: (data) =>
            resolveSubjectUrl(
                data.subject as NotificationSubject,
                data.meta as Record<string, unknown>,
            ),
    },
    [RESOURCE_UNARCHIVED]: {
        icon: ArchiveRestore,
        label: 'Resource unarchived',
        resolveUrl: (data) =>
            resolveSubjectUrl(
                data.subject as NotificationSubject,
                data.meta as Record<string, unknown>,
            ),
    },
    [MEMBER_ROLE_CHANGED]: {
        icon: UserCog,
        label: 'Role changed',
        resolveUrl: (data) =>
            resolveSubjectUrl(
                data.subject as NotificationSubject,
                data.meta as Record<string, unknown>,
            ),
    },
    [MEMBER_REMOVED]: {
        icon: UserMinus,
        label: 'Member removed',
        resolveUrl: (data) =>
            resolveSubjectUrl(
                data.subject as NotificationSubject,
                data.meta as Record<string, unknown>,
            ),
    },
    [RESOURCE_MESSAGE]: {
        icon: MessageSquare,
        label: 'Notification',
        resolveUrl: (data) =>
            resolveSubjectUrl(
                data.subject as NotificationSubject,
                data.meta as Record<string, unknown>,
            ),
    },
};
