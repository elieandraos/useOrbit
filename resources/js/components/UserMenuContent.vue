<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings } from '@lucide/vue';
import { Separator } from '@/components/ui/separator';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
};

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <div class="flex items-center gap-2 px-2 py-1.5 text-left text-sm">
        <UserInfo :user="user" :show-email="true" />
    </div>
    <Separator class="my-1" />
    <Link
        class="flex items-center rounded-[6px] px-2 py-1.5 text-sm text-primary hover:bg-sunken"
        :href="edit()"
        prefetch
    >
        <Settings class="mr-2 h-4 w-4" />
        Settings
    </Link>
    <Separator class="my-1" />
    <Link
        class="flex w-full items-center rounded-[6px] px-2 py-1.5 text-left text-sm text-secondary hover:bg-sunken"
        :href="logout()"
        @click="handleLogout"
        as="button"
        data-test="logout-button"
    >
        <LogOut class="mr-2 h-4 w-4" />
        Log out
    </Link>
</template>
