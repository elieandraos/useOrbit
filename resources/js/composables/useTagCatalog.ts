import { useHttp } from '@inertiajs/vue3';
import type { ComputedRef } from 'vue';
import { computed, reactive } from 'vue';
import { store as storeTag } from '@/routes/tags';
import type { TagResource } from '@/types/tag';

export type UseTagCatalogReturn = {
    tags: ComputedRef<TagResource[]>;
    syncTags: (tags: TagResource[]) => void;
    upsertTag: (tag: TagResource) => void;
    createTag: (name: string) => Promise<TagResource>;
};

type ScopeTagState = {
    tagsById: Record<number, TagResource>;
};

// Module-scoped (not component-scoped) so the catalog survives Inertia
// navigation between one client's Documents tab and another's — tags are
// shared org-wide, unlike useDocumentUploads' per-client document state.
// Keyed by scope to mirror that module's stateByScope shape; this app has
// no frontend concept of switching organizations today, so there is only
// ever one active key per session.
const ORGANIZATION_SCOPE = 'organization';

const stateByScope = reactive<Record<string, ScopeTagState>>({});

function scopeState(scopeKey: string): ScopeTagState {
    if (!stateByScope[scopeKey]) {
        stateByScope[scopeKey] = { tagsById: {} };
    }

    return stateByScope[scopeKey];
}

export function useTagCatalog(): UseTagCatalogReturn {
    const state = scopeState(ORGANIZATION_SCOPE);

    function syncTags(tags: TagResource[]): void {
        tags.forEach((tag) => {
            state.tagsById[tag.id] = tag;
        });
    }

    function upsertTag(tag: TagResource): void {
        state.tagsById[tag.id] = tag;
    }

    function createTag(name: string): Promise<TagResource> {
        return new Promise((resolve, reject) => {
            useHttp<{ name: string }, TagResource>({ name })
                .post(storeTag().url, {
                    onSuccess: (tag) => {
                        state.tagsById[tag.id] = tag;
                        resolve(tag);
                    },
                    onError: () => {
                        reject(new Error('Failed to create tag.'));
                    },
                })
                .catch(() => {
                    // onError above already rejected this promise; useHttp
                    // rethrows after that callback, so swallow it here to
                    // avoid an unhandled promise rejection.
                });
        });
    }

    const tags = computed(() =>
        Object.values(state.tagsById).sort((a, b) =>
            a.name.localeCompare(b.name),
        ),
    );

    return {
        tags,
        syncTags,
        upsertTag,
        createTag,
    };
}
