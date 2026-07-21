<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, ChevronDown, Menu } from '@lucide/vue';
import { computed, onMounted } from 'vue';
import NotificationRow from '@/components/notifications/NotificationRow.vue';
import AppLogo from '@/components/shell/AppLogo.vue';
import { navItems } from '@/components/shell/navItems';
import UserInfo from '@/components/shell/UserInfo.vue';
import { Avatar } from '@/components/ui/avatar';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useNotifications } from '@/composables/useNotifications';
import { dashboard, logout } from '@/routes';
import { index as notificationsIndex } from '@/routes/notifications';
import { edit } from '@/routes/profile';

const RECENT_NOTIFICATIONS_LIMIT = 5;

const page = usePage();
const user = computed(() => page.props.auth.user);

const { isCurrentOrParentUrl } = useCurrentUrl();

const { items, unreadCount, fetchItems, markAsRead } = useNotifications();
const recentItems = computed(() => items.slice(0, RECENT_NOTIFICATIONS_LIMIT));

onMounted(fetchItems);

const mobileNavOpen = defineModel<boolean>('mobileNavOpen', {
    default: false,
});
</script>

<template>
    <header
        class="flex h-14 items-center border-b border-border bg-surface px-4 md:px-8"
    >
        <button
            type="button"
            class="mr-3 inline-flex size-9 shrink-0 items-center justify-center rounded-md text-primary transition-colors hover:bg-sunken md:hidden"
            @click="mobileNavOpen = true"
        >
            <Menu class="size-5" />
        </button>

        <Link :href="dashboard()" class="flex items-center">
            <AppLogo />
        </Link>

        <nav class="ml-8 hidden h-full items-stretch gap-1 md:flex">
            <Link
                v-for="item in navItems"
                :key="item.label"
                :href="item.href"
                class="flex items-center gap-1.5 border-b-2 px-3 text-sm font-medium transition-colors"
                :class="
                    isCurrentOrParentUrl(item.href)
                        ? 'border-accent text-accent'
                        : 'border-transparent text-secondary hover:text-primary'
                "
            >
                <component :is="item.icon" class="size-4" />
                {{ item.label }}
            </Link>
        </nav>

        <div class="ml-auto flex items-center gap-1">
            <DropMenu
                align="end"
                panel-class="w-[340px] max-h-[420px] overflow-y-auto"
            >
                <template #trigger>
                    <button
                        type="button"
                        class="flex size-9 items-center justify-center rounded-md text-secondary transition-colors hover:bg-sunken hover:text-primary"
                    >
                        <span class="relative inline-flex">
                            <Bell class="size-[18px]" />
                            <span
                                v-if="unreadCount > 0"
                                class="absolute -top-1.5 -right-1.5 flex h-4 min-w-4 items-center justify-center rounded-pill bg-accent px-1 text-[10px] font-semibold text-white ring-2 ring-surface"
                            >
                                {{ unreadCount > 99 ? '99+' : unreadCount }}
                            </span>
                        </span>
                    </button>
                </template>

                <div class="px-2 py-1.5 text-sm font-semibold text-primary">
                    Notifications
                </div>
                <Separator class="my-1" />
                <div
                    v-if="recentItems.length === 0"
                    class="px-2 py-6 text-center text-sm text-tertiary"
                >
                    No notifications yet
                </div>
                <div v-else class="flex flex-col">
                    <NotificationRow
                        v-for="item in recentItems"
                        :key="item.id"
                        :notification="item"
                        dense
                        @select="markAsRead"
                    />
                </div>
                <Separator class="my-1" />
                <DropMenuItem
                    :href="notificationsIndex()"
                    class="justify-center text-accent"
                >
                    View all
                </DropMenuItem>
            </DropMenu>

            <DropMenu align="end">
                <template #trigger>
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-pill p-1 transition-colors hover:bg-sunken"
                    >
                        <Avatar
                            :name="user.name"
                            :src="user.avatar ?? undefined"
                            size="sm"
                        />
                        <ChevronDown class="size-3.5 text-secondary" />
                    </button>
                </template>

                <div class="flex items-center gap-2 px-2 py-1.5 text-sm">
                    <UserInfo :user="user" :show-email="true" />
                </div>
                <Separator class="my-1" />
                <DropMenuItem :href="edit()">Settings</DropMenuItem>
                <Separator class="my-1" />
                <DropMenuItem
                    :href="logout()"
                    method="post"
                    as="button"
                    data-test="logout-button"
                >
                    Log out
                </DropMenuItem>
            </DropMenu>
        </div>
    </header>
</template>
