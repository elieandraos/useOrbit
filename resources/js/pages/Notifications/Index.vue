<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
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

const hasNotifications = computed(() => props.notifications.data.length > 0);
const hasUnread = computed(() =>
    props.notifications.data.some(
        (notification) => notification.read_at === null,
    ),
);
</script>

<template>
    <Head title="Notifications" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="Notifications"
            subtitle="Batch upload results and account activity across your book of business"
        >
            <template v-if="hasUnread" #actions>
                <Button variant="ghost" size="md" @click="markAllAsRead">
                    Mark all as read
                </Button>
            </template>
        </PageHeader>

        <Card v-if="hasNotifications" class="mt-5 overflow-hidden">
            <CardContent class="divide-y divide-border-subtle p-0">
                <NotificationRow
                    v-for="notification in notifications.data"
                    :key="notification.id"
                    :notification="notification"
                    @select="markAsRead"
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
