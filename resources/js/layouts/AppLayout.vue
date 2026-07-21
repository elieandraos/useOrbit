<script setup lang="ts">
import { ref } from 'vue';
import AppContent from '@/components/shell/AppContent.vue';
import AppShell from '@/components/shell/AppShell.vue';
import AppTopNav from '@/components/shell/AppTopNav.vue';
import NavDrawer from '@/components/shell/NavDrawer.vue';
import { Toaster } from '@/components/ui/sonner';
import { useNotificationsListener } from '@/composables/useNotificationsListener';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    breadcrumbs?: BreadcrumbItem[];
    /** Passed through to AppContent — see its prop doc for what this does. */
    breadcrumbsSurface?: boolean;
}>();

const mobileNavOpen = ref(false);

useNotificationsListener();
</script>

<template>
    <AppShell>
        <AppTopNav v-model:mobile-nav-open="mobileNavOpen" />
        <NavDrawer v-model:open="mobileNavOpen" />
        <AppContent
            :breadcrumbs="breadcrumbs"
            :breadcrumbs-surface="breadcrumbsSurface"
        >
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
