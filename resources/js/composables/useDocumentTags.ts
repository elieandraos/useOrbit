import type { UseTaggableAttachmentsReturn } from '@/composables/useTaggableAttachments';
import { useTaggableAttachments } from '@/composables/useTaggableAttachments';
import {
    destroy as detachTagRoute,
    store as attachTagRoute,
} from '@/routes/documents/tags';
import type { DocumentResource } from '@/types/document';

export type UseDocumentTagsReturn = UseTaggableAttachmentsReturn;

export function useDocumentTags(
    documentsById: Record<number, DocumentResource>,
): UseDocumentTagsReturn {
    return useTaggableAttachments(
        documentsById,
        attachTagRoute,
        detachTagRoute,
    );
}
