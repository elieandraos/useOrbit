<script setup lang="ts">
import { Pagination } from '@/components/ui/pagination';

const meta = {
    current_page: 1,
    last_page: 3,
    from: 1,
    to: 15,
    total: 42,
    per_page: 15,
    links: [
        { url: null, label: '&laquo; Previous', active: false },
        { url: '/clients?page=1', label: '1', active: true },
        { url: '/clients?page=2', label: '2', active: false },
        { url: '/clients?page=3', label: '3', active: false },
        { url: '/clients?page=2', label: 'Next &raquo;', active: false },
    ],
};
</script>

<template>
    <Pagination :meta="meta" item-label="clients" />
</template>
