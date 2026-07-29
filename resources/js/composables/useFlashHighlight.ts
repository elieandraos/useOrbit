import { reactive } from 'vue';

const FLASH_DURATION_MS = 1800;

export type UseFlashHighlightReturn = {
    flash: (id: number) => void;
    isFlashing: (id: number) => boolean;
};

// Generic "flash this row" primitive: call `flash(id)` right after an item
// is created so its row can play a highlight animation. Deliberately
// id-based rather than tied to how the id was learned about, so the same
// call can later be driven by a notification-arrival id (e.g. a query param
// read on mount) instead of only a same-page creation, without reshaping
// this composable.
export function useFlashHighlight(): UseFlashHighlightReturn {
    const flashedIds = reactive(new Set<number>());

    function flash(id: number): void {
        flashedIds.add(id);
        window.setTimeout(() => flashedIds.delete(id), FLASH_DURATION_MS);
    }

    function isFlashing(id: number): boolean {
        return flashedIds.has(id);
    }

    return { flash, isFlashing };
}
