<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';
import NotificationRow from '@/components/notifications/NotificationRow.vue';
import PageHeader from '@/components/shell/PageHeader.vue';
import Button from '@/components/ui/button/Button.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Pagination } from '@/components/ui/pagination';
import { useNotifications } from '@/composables/useNotifications';
import type { Paginated } from '@/types';
import type { NotificationItem } from '@/types/notification';

const props = defineProps<{
    notifications: Paginated<NotificationItem>;
}>();

const { markAsRead, markAllAsRead } = useNotifications();

// The dropdown in AppTopNav renders its own copy of notifications fetched
// via useNotifications, so marking a notification read there doesn't touch
// this page's server-provided list. Keep a local reactive copy here and
// mutate it directly so a row's unread styling updates immediately.
const items = reactive<NotificationItem[]>([...props.notifications.data]);

watch(
    () => props.notifications.data,
    (data) => {
        items.splice(0, items.length, ...data);
    },
);

const hasNotifications = computed(() => items.length > 0);
const hasUnread = computed(() =>
    items.some((notification) => notification.read_at === null),
);

function handleSelect(id: string): void {
    const item = items.find((notification) => notification.id === id);

    if (item) {
        item.read_at = new Date().toISOString();
    }

    markAsRead(id);
}

function handleMarkAllAsRead(): void {
    const now = new Date().toISOString();
    items.forEach((item) => {
        item.read_at = now;
    });

    markAllAsRead();
}
</script>

<template>
    <Head title="Notifications" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="Notifications"
            subtitle="Batch upload results and account activity across your book of business"
        >
            <template v-if="hasUnread" #actions>
                <Button variant="ghost" size="md" @click="handleMarkAllAsRead">
                    Mark all as read
                </Button>
            </template>
        </PageHeader>

        <Card v-if="hasNotifications" class="mt-5 overflow-hidden">
            <CardContent class="divide-y divide-border-subtle p-0">
                <NotificationRow
                    v-for="notification in items"
                    :key="notification.id"
                    :notification="notification"
                    @select="handleSelect"
                />
            </CardContent>
            <Pagination :meta="notifications.meta" item-label="notifications" />
        </Card>
        <div
            v-else
            class="flex flex-1 items-center justify-center py-16 text-sm text-tertiary"
        >
            No notifications yet.
        </div>
    </div>
</template>
