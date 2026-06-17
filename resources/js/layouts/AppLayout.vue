<script setup lang="ts">
import AppContent from '@/components/shell/AppContent.vue';
import AppShell from '@/components/shell/AppShell.vue';
import AppTopNav from '@/components/shell/AppTopNav.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <AppShell>
        <AppTopNav />
        <AppContent>
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
