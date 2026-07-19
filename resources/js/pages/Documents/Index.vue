<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { Paginated } from '@/types';
import type { ClientResource } from '../Clients/partials/client';
import type { DocumentResource } from './partials/document';

defineProps<{
    client: ClientResource;
    documents: Paginated<DocumentResource>;
}>();
</script>

<template>
    <Head :title="`${client.full_name} · Documents`" />

    <div class="flex flex-1 flex-col gap-4">
        <ul class="divide-y divide-border-subtle">
            <li v-for="document in documents.data" :key="document.id" class="py-3">
                {{ document.original_filename }}
            </li>
        </ul>
    </div>
</template>