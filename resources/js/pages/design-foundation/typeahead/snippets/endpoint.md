<script setup lang="ts">
import { ref } from 'vue';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import { Typeahead } from '@/components/ui/typeahead';
import type { TypeaheadOption } from '@/components/ui/typeahead';

type Person = { id: number; name: string; email: string };

type PersonOption = TypeaheadOption & { email: string };

// Dev-only demo endpoint serving synthetic people. It is registered only in
// the local environment, so its Wayfinder route isn't generated in CI or
// production builds — the URL is written out like the design-foundation nav.
const SEARCH_URL = '/design-foundation/typeahead/search';

async function searchPeople(query: string): Promise<PersonOption[]> {
    const response = await fetch(
        `${SEARCH_URL}?${new URLSearchParams({ q: query })}`,
        { headers: { Accept: 'application/json' } },
    );

    if (!response.ok) {
        return [];
    }

    const { data } = (await response.json()) as { data: Person[] };

    return data.map((person) => ({
        value: person.id,
        label: person.name,
        email: person.email,
    }));
}

const personId = ref<number | string | null>(null);
const personName = ref<string | null>(null);
</script>

<template>
    <Typeahead
        v-model="personId"
        v-model:label="personName"
        name="person_id"
        :search="searchPeople"
        :min-query-length="2"
        placeholder="Search people…"
    >
        <template #option="{ option }">
            <div class="flex items-center gap-2">
                <Avatar :name="option.label" size="sm" />
                <div class="flex flex-col">
                    <span>{{ option.label }}</span>
                    <span class="text-xs text-tertiary">{{
                        option.email
                    }}</span>
                </div>
            </div>
        </template>

        <template #selected="{ label }">
            <Avatar :name="label" size="sm" />
            <span class="truncate">{{ label }}</span>
        </template>
    </Typeahead>
    <p class="text-sm text-secondary">Value: {{ personId }}</p>
    <p class="text-sm text-secondary">Label: {{ personName }}</p>

</template>
