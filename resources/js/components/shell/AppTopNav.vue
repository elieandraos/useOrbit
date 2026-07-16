<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, Menu } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/shell/AppLogo.vue';
import { navItems } from '@/components/shell/navItems';
import UserInfo from '@/components/shell/UserInfo.vue';
import { Avatar } from '@/components/ui/avatar';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard, logout } from '@/routes';
import { edit } from '@/routes/profile';

const page = usePage();
const user = computed(() => page.props.auth.user);

const { isCurrentOrParentUrl } = useCurrentUrl();

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

        <DropMenu class="ml-auto">
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
    </header>
</template>
