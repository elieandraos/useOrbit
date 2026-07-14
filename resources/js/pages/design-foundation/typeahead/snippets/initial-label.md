<script setup lang="ts">
import { ref } from 'vue';
import { Typeahead } from '@/components/ui/typeahead';
import type { TypeaheadOption } from '@/components/ui/typeahead';

const clients: TypeaheadOption[] = [
    { value: 1, label: 'Marie Khalil' },
    { value: 5, label: 'Layla Mansour' },
];

function searchClients(query: string): Promise<TypeaheadOption[]> {
    return new Promise((resolve) => {
        setTimeout(() => {
            const needle = query.trim().toLowerCase();

            resolve(needle ? clients.filter((client) => client.label.toLowerCase().includes(needle)) : clients);
        }, 600);
    });
}

// The client is already known (e.g. an edit form) but the options haven't
// been fetched yet — `initialLabel` renders the value immediately.
const client = ref<number | string | null>(5);
</script>

<template>
    <Typeahead v-model="client" :search="searchClients" initial-label="Layla Mansour" placeholder="Search clients…" />
</template>
