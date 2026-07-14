<script setup lang="ts">
import { ref } from 'vue';
import { Typeahead } from '@/components/ui/typeahead';
import type { TypeaheadOption } from '@/components/ui/typeahead';

// Stand-in for an endpoint like `GET /clients/search?q=`.
const clients: TypeaheadOption[] = [
    { value: 1, label: 'Marie Khalil' },
    { value: 2, label: 'Karim Fares' },
    { value: 3, label: 'Nour Abdallah' },
    { value: 4, label: 'Sami Haddad' },
    { value: 5, label: 'Layla Mansour' },
    { value: 6, label: 'Rami Choueiri' },
    { value: 7, label: 'Dana Saab' },
    { value: 8, label: 'Elie Tannous' },
];

function searchClients(query: string): Promise<TypeaheadOption[]> {
    return new Promise((resolve) => {
        setTimeout(() => {
            const needle = query.trim().toLowerCase();

            resolve(needle ? clients.filter((client) => client.label.toLowerCase().includes(needle)) : clients);
        }, 600);
    });
}

const client = ref<number | string | null>(null);
</script>

<template>
    <Typeahead v-model="client" :search="searchClients" placeholder="Search clients…" />
    <p class="text-sm text-secondary">Value: {{ client }}</p>
</template>
