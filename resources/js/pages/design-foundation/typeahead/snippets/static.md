<script setup lang="ts">
import { ref } from 'vue';
import { Typeahead } from '@/components/ui/typeahead';
import type { TypeaheadOption } from '@/components/ui/typeahead';

const teamMembers: TypeaheadOption[] = [
    { value: 1, label: 'Marie Khalil' },
    { value: 2, label: 'Karim Fares' },
    { value: 3, label: 'Nour Abdallah' },
    { value: 4, label: 'Sami Haddad' },
    { value: 5, label: 'Layla Mansour' },
];

const assignee = ref<number | string | null>(null);
</script>

<template>
    <Typeahead v-model="assignee" :options="teamMembers" placeholder="Assign to" />
    <p class="text-sm text-secondary">Value: {{ assignee }}</p>
</template>
