<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import { Avatar } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import UserInfo from '@/components/UserInfo.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard, logout } from '@/routes';
import { edit } from '@/routes/profile';

const page = usePage();
const user = computed(() => page.props.auth.user);

const { isCurrentOrParentUrl } = useCurrentUrl();

const menuOpen = ref(false);
const menuRef = ref<HTMLElement | null>(null);

function toggleMenu() {
    menuOpen.value = !menuOpen.value;
}

function closeMenu() {
    menuOpen.value = false;
}

function handleClickOutside(event: MouseEvent) {
    if (menuRef.value && !menuRef.value.contains(event.target as Node)) {
        closeMenu();
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onBeforeUnmount(() =>
    document.removeEventListener('click', handleClickOutside),
);
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
                class="flex items-center border-b-2 px-3 text-sm font-medium transition-colors"
                :class="
                    isCurrentOrParentUrl(dashboard())
                        ? 'border-accent text-accent'
                        : 'border-transparent text-secondary hover:text-primary'
                "
            >
                Dashboard
            </Link>
        </nav>

        <div ref="menuRef" class="relative ml-auto">
            <button
                type="button"
                class="flex items-center gap-2 rounded-pill p-1 transition-colors hover:bg-sunken"
                @click="toggleMenu"
            >
                <Avatar
                    :name="user.name"
                    :src="user.avatar ?? undefined"
                    size="sm"
                />
            </button>

            <div
                v-if="menuOpen"
                class="absolute top-[calc(100%+6px)] right-0 z-20 min-w-[220px] rounded-[10px] border border-border bg-surface p-1 shadow-lg"
            >
                <div
                    class="flex items-center gap-2 px-2 py-1.5 text-left text-sm"
                >
                    <UserInfo :user="user" :show-email="true" />
                </div>
                <Separator class="my-1" />
                <Link
                    :href="edit()"
                    class="flex items-center rounded-[6px] px-2 py-1.5 text-sm text-primary hover:bg-sunken"
                    @click="closeMenu"
                >
                    Settings
                </Link>
                <Separator class="my-1" />
                <Link
                    :href="logout()"
                    method="post"
                    as="button"
                    class="flex w-full items-center rounded-[6px] px-2 py-1.5 text-left text-sm text-secondary hover:bg-sunken"
                    data-test="logout-button"
                >
                    Log out
                </Link>
            </div>
        </div>
    </header>
</template>
