<script setup lang="ts">
import { Pagination } from '@/components/ui/pagination';

const meta = {
    current_page: 3,
    last_page: 3,
    from: 31,
    to: 42,
    total: 42,
    per_page: 15,
    links: [
        { url: '/clients?page=2', label: '&laquo; Previous', active: false },
        { url: '/clients?page=1', label: '1', active: false },
        { url: '/clients?page=2', label: '2', active: false },
        { url: '/clients?page=3', label: '3', active: true },
        { url: null, label: 'Next &raquo;', active: false },
    ],
};
</script>

<template>
    <Pagination :meta="meta" item-label="clients" />
</template>
