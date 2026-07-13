<script setup lang="ts">
import { ref } from 'vue';
import AppContent from '@/components/shell/AppContent.vue';
import AppShell from '@/components/shell/AppShell.vue';
import AppTopNav from '@/components/shell/AppTopNav.vue';
import NavDrawer from '@/components/shell/NavDrawer.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const mobileNavOpen = ref(false);
</script>

<template>
    <AppShell>
        <AppTopNav v-model:mobile-nav-open="mobileNavOpen" />
        <NavDrawer v-model:open="mobileNavOpen" />
        <AppContent :breadcrumbs="breadcrumbs">
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
