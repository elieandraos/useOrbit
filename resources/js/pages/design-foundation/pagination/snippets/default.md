<script setup lang="ts">
import { Pagination } from '@/components/ui/pagination';

const meta = {
    current_page: 2,
    last_page: 4,
    from: 16,
    to: 30,
    total: 62,
    per_page: 15,
    links: [
        { url: '/clients?page=1', label: '&laquo; Previous', active: false },
        { url: '/clients?page=1', label: '1', active: false },
        { url: '/clients?page=2', label: '2', active: true },
        { url: '/clients?page=3', label: '3', active: false },
        { url: '/clients?page=4', label: '4', active: false },
        { url: '/clients?page=3', label: 'Next &raquo;', active: false },
    ],
};
</script>

<template>
    <Pagination :meta="meta" item-label="clients" />
</template>
