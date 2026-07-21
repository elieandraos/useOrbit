import { useHttp, usePage } from '@inertiajs/vue3';
import type { ComputedRef } from 'vue';
import { computed, reactive } from 'vue';
import { read, readAll, recent } from '@/routes/notifications';
import type { NotificationItem } from '@/types/notification';

export type UseNotificationsReturn = {
    items: NotificationItem[];
    unreadCount: ComputedRef<number>;
    fetchItems: () => void;
    markAsRead: (id: string) => void;
    markAllAsRead: () => void;
};

const state = reactive<{ items: NotificationItem[]; unreadCount: number }>({
    items: [],
    unreadCount: 0,
});

let unreadCountBootstrapped = false;
let itemsRequested = false;

export function useNotifications(): UseNotificationsReturn {
    const page = usePage();

    if (!unreadCountBootstrapped) {
        state.unreadCount = page.props.notifications.unreadCount;
        unreadCountBootstrapped = true;
    }

    function fetchItems(): void {
        if (itemsRequested) {
            return;
        }

        itemsRequested = true;

        useHttp({}).get(recent().url, {
            onSuccess: (response) => {
                state.items = response as NotificationItem[];
            },
            onError: () => {
                itemsRequested = false;
            },
        });
    }

    function markAsRead(id: string): void {
        const item = state.items.find((notification) => notification.id === id);

        if (!item || item.read_at) {
            return;
        }

        item.read_at = new Date().toISOString();
        state.unreadCount = Math.max(0, state.unreadCount - 1);

        useHttp({}).post(read(id).url, {
            onError: () => {
                item.read_at = null;
                state.unreadCount += 1;
            },
        });
    }

    function markAllAsRead(): void {
        const unreadItems = state.items.filter(
            (notification) => !notification.read_at,
        );

        if (unreadItems.length === 0 && state.unreadCount === 0) {
            return;
        }

        const now = new Date().toISOString();
        unreadItems.forEach((notification) => {
            notification.read_at = now;
        });
        const previousUnreadCount = state.unreadCount;
        state.unreadCount = 0;

        useHttp({}).post(readAll().url, {
            onError: () => {
                unreadItems.forEach((notification) => {
                    notification.read_at = null;
                });
                state.unreadCount = previousUnreadCount;
            },
        });
    }

    return {
        items: state.items,
        unreadCount: computed(() => state.unreadCount),
        fetchItems,
        markAsRead,
        markAllAsRead,
    };
}