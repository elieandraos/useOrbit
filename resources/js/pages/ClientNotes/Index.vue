<script setup lang="ts">
import type { ClientResource } from '../Clients/partials/client';
import ClientDetailShell from '../Clients/partials/ClientDetailShell.vue';

interface NoteResource {
    id: number;
    body: string;
    pinned: boolean;
    created_by_name: string | null;
    created_at: string;
    can_update: boolean;
    can_delete: boolean;
}

defineProps<{
    client: ClientResource;
    notes: NoteResource[];
}>();
</script>

<template>
    <ClientDetailShell :client="client" :policies-count="0">
        <p class="py-6 text-sm text-muted-foreground">{{ notes.length }} note(s).</p>
    </ClientDetailShell>
</template>
