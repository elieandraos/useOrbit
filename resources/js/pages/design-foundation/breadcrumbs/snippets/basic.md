<script setup lang="ts">
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clients', href: '/clients' },
    { title: 'Create' },
];
</script>

<template>
    <Breadcrumbs :breadcrumbs="breadcrumbs" />
</template>