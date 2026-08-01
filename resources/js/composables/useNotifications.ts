import { useHttp, usePage } from '@inertiajs/vue3';
import type { ComputedRef } from 'vue';
import { computed, reactive } from 'vue';
import { toast } from 'vue-sonner';
import { read, readAll, recent } from '@/routes/notifications';
import type {
    NotificationItem,
    RecentNotificationsResponse,
} from '@/types/notification';

export type UseNotificationsReturn = {
    items: NotificationItem[];
    unreadCount: ComputedRef<number>;
    hasMore: ComputedRef<boolean>;
    loadingMore: ComputedRef<boolean>;
    fetchItems: () => void;
    loadMore: () => void;
    markAsRead: (id: string) => void;
    markAllAsRead: () => void;
    receiveNotification: (notification: {
        id: string;
        type: string;
        data: Record<string, unknown>;
    }) => void;
};

const state = reactive<{
    items: NotificationItem[];
    unreadCount: number;
    nextCursor: string | null;
    hasMore: boolean;
    loadingMore: boolean;
}>({
    items: [],
    unreadCount: 0,
    nextCursor: null,
    hasMore: true,
    loadingMore: false,
});

let unreadCountBootstrapped = false;
let itemsRequested = false;

export function useNotifications(): UseNotificationsReturn {
    const page = usePage();

    if (!unreadCountBootstrapped) {
        state.unreadCount = page.props.notifications.unreadCount;
        unreadCountBootstrapped = true;
    }

    // The dropdown's page of 15 is ordered unread-first, then most-recent
    // first within each group (see NotificationsListController). Once a
    // fetched page contains zero unread items we've crossed into read
    // history, so auto-loading stops there even if more pages exist —
    // "View all" is the path to the rest.
    function applyPage(
        response: RecentNotificationsResponse,
        { append }: { append: boolean },
    ): void {
        if (append) {
            state.items.push(...response.data);
        } else {
            state.items.splice(0, state.items.length, ...response.data);
        }

        state.nextCursor = response.next_cursor;

        const pageHasUnread = response.data.some(
            (item) => item.read_at === null,
        );
        state.hasMore = Boolean(response.next_cursor) && pageHasUnread;
    }

    function fetchItems(): void {
        if (itemsRequested) {
            return;
        }

        itemsRequested = true;

        useHttp({}).get(recent().url, {
            onSuccess: (response) => {
                applyPage(response as RecentNotificationsResponse, {
                    append: false,
                });
            },
            onError: () => {
                itemsRequested = false;
                toast.error("Couldn't load notifications.");
            },
        });
    }

    function loadMore(): void {
        if (state.loadingMore || !state.hasMore || !state.nextCursor) {
            return;
        }

        state.loadingMore = true;

        useHttp({}).get(recent({ query: { cursor: state.nextCursor } }).url, {
            onSuccess: (response) => {
                applyPage(response as RecentNotificationsResponse, {
                    append: true,
                });
                state.loadingMore = false;
            },
            onError: () => {
                state.loadingMore = false;
                toast.error("Couldn't load more notifications.");
            },
        });
    }

    function markAsRead(id: string): void {
        const item = state.items.find((notification) => notification.id === id);

        if (item?.read_at) {
            return;
        }

        if (item) {
            item.read_at = new Date().toISOString();
        }

        state.unreadCount = Math.max(0, state.unreadCount - 1);

        useHttp({}).post(read(id).url, {
            onError: () => {
                if (item) {
                    item.read_at = null;
                }

                state.unreadCount += 1;
                toast.error("Couldn't mark notification as read.");
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
                toast.error("Couldn't mark all notifications as read.");
            },
        });
    }

    function receiveNotification(notification: {
        id: string;
        type: string;
        data: Record<string, unknown>;
    }): void {
        state.items.unshift({
            id: notification.id,
            type: notification.type,
            data: notification.data,
            read_at: null,
            created_at: 'Just now',
        });
        state.unreadCount += 1;
    }

    return {
        items: state.items,
        unreadCount: computed(() => state.unreadCount),
        hasMore: computed(() => state.hasMore),
        loadingMore: computed(() => state.loadingMore),
        fetchItems,
        loadMore,
        markAsRead,
        markAllAsRead,
        receiveNotification,
    };
}
