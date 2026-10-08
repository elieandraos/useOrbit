<script setup lang="ts">
import Avatar from '@/components/ui/avatar/Avatar.vue';
import { Typeahead } from '@/components/ui/typeahead';
import type { TypeaheadOption } from '@/components/ui/typeahead';
import { search as clientsSearch } from '@/routes/clients';

interface ClientSearchResult {
    id: number;
    full_name: string;
}

defineProps<{
    name?: string;
}>();

const clientId = defineModel<number | string | null>({ default: null });

/** The selected client's displayed name, which always comes from the server. */
const clientName = defineModel<string | null>('label', { default: null });

async function searchClients(query: string): Promise<TypeaheadOption[]> {
    const response = await fetch(
        clientsSearch.url({ query: { search: query.trim() } }),
        { headers: { Accept: 'application/json' } },
    );

    // A rejected search redirects back (validation errors only render as JSON under api/*).
    if (!response.ok || response.redirected) {
        return [];
    }

    const { data } = (await response.json()) as {
        data: ClientSearchResult[];
    };

    return data.map((client) => ({
        value: client.id,
        label: client.full_name,
    }));
}
</script>

<template>
    <Typeahead
        v-model="clientId"
        v-model:label="clientName"
        :name="name"
        :search="searchClients"
        :min-query-length="2"
        placeholder="Search clients…"
    >
        <template #option="{ option }">
            <div class="flex min-w-0 items-center gap-2">
                <Avatar :name="option.label" size="sm" />
                <span class="truncate">{{ option.label }}</span>
            </div>
        </template>

        <template #selected="{ label }">
            <Avatar :name="label" size="sm" />
            <span class="truncate">{{ label }}</span>
        </template>
    </Typeahead>
</template>
