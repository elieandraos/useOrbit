<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, Home, Users } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/shell/AppLogo.vue';
import UserInfo from '@/components/shell/UserInfo.vue';
import { Avatar } from '@/components/ui/avatar';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard, logout } from '@/routes';
import { index as clientsIndex } from '@/routes/clients';
import { edit } from '@/routes/profile';

const page = usePage();
const user = computed(() => page.props.auth.user);

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <header
        class="flex h-14 items-center border-b border-border bg-surface px-8"
    >
        <Link :href="dashboard()" class="flex items-center">
            <AppLogo />
        </Link>

        <nav class="ml-8 flex h-full items-stretch gap-1">
            <Link
                :href="dashboard()"
                class="flex items-center gap-1.5 border-b-2 px-3 text-sm font-medium transition-colors"
                :class="
                    isCurrentOrParentUrl(dashboard())
                        ? 'border-accent text-accent'
                        : 'border-transparent text-secondary hover:text-primary'
                "
            >
                <Home class="size-4" />
                Dashboard
            </Link>

            <Link
                :href="clientsIndex()"
                class="flex items-center gap-1.5 border-b-2 px-3 text-sm font-medium transition-colors"
                :class="
                    isCurrentOrParentUrl(clientsIndex())
                        ? 'border-accent text-accent'
                        : 'border-transparent text-secondary hover:text-primary'
                "
            >
                <Users class="size-4" />
                Clients
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
