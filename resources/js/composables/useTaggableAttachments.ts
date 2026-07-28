import { useHttp } from '@inertiajs/vue3';
import { useTagCatalog } from '@/composables/useTagCatalog';
import type { TagResource } from '@/types/tag';

export type TaggableItem = {
    id: number;
    tags: TagResource[];
};

type AttachmentRoute = (args: [itemId: number, tagId: number]) => {
    url: string;
};

export type UseTaggableAttachmentsReturn = {
    attachTag: (itemId: number, tagId: number) => Promise<void>;
    detachTag: (itemId: number, tagId: number) => Promise<void>;
};

export function useTaggableAttachments<T extends TaggableItem>(
    itemsById: Record<number, T>,
    attachRoute: AttachmentRoute,
    detachRoute: AttachmentRoute,
): UseTaggableAttachmentsReturn {
    const { tags: tagCatalog, upsertTag } = useTagCatalog();

    async function attachTag(itemId: number, tagId: number): Promise<void> {
        const tags = await useHttp<Record<string, never>, TagResource[]>(
            {},
        ).post(attachRoute([itemId, tagId]).url);

        const item = itemsById[itemId];

        if (item) {
            item.tags = tags;
        }

        const attached = tags.find((tag) => tag.id === tagId);

        if (attached) {
            upsertTag(attached);
        }
    }

    async function detachTag(itemId: number, tagId: number): Promise<void> {
        const tags = await useHttp<Record<string, never>, TagResource[]>(
            {},
        ).delete(detachRoute([itemId, tagId]).url);

        const item = itemsById[itemId];

        if (item) {
            item.tags = tags;
        }

        // The detached tag no longer appears in the response (it's the
        // item's remaining tags), so its fresh usage_count isn't
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
