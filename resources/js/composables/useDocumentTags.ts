import { useHttp } from '@inertiajs/vue3';
import { useTagCatalog } from '@/composables/useTagCatalog';
import {
    destroy as detachTagRoute,
    store as attachTagRoute,
} from '@/routes/documents/tags';
import type { DocumentResource } from '@/types/document';
import type { TagResource } from '@/types/tag';

export type UseDocumentTagsReturn = {
    attachTag: (documentId: number, tagId: number) => Promise<void>;
    detachTag: (documentId: number, tagId: number) => Promise<void>;
};

export function useDocumentTags(
    documentsById: Record<number, DocumentResource>,
): UseDocumentTagsReturn {
    const { tags: tagCatalog, upsertTag } = useTagCatalog();

    async function attachTag(documentId: number, tagId: number): Promise<void> {
        const tags = await useHttp<Record<string, never>, TagResource[]>(
            {},
        ).post(attachTagRoute([documentId, tagId]).url);

        const document = documentsById[documentId];

        if (document) {
            document.tags = tags;
        }

        const attached = tags.find((tag) => tag.id === tagId);

        if (attached) {
            upsertTag(attached);
        }
    }

    async function detachTag(documentId: number, tagId: number): Promise<void> {
        const tags = await useHttp<Record<string, never>, TagResource[]>(
            {},
        ).delete(detachTagRoute([documentId, tagId]).url);

        const document = documentsById[documentId];

        if (document) {
            document.tags = tags;
        }

        // The detached tag no longer appears in the response (it's the
        // document's remaining tags), so its fresh usage_count isn't
        // available here — patch the catalog optimistically instead of a
        // full refetch.
        const current = tagCatalog.value.find((tag) => tag.id === tagId);

        if (current) {
            upsertTag({
                ...current,
                usage_count: Math.max(0, (current.usage_count ?? 1) - 1),
            });
        }
    }

    return { attachTag, detachTag };
}
