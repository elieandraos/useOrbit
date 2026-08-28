<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import {
    index as carriersIndex,
    store as carriersStore,
} from '@/routes/carriers';
import CarrierForm from './partials/CarrierForm.vue';

defineProps<{
    countries: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Carriers',
                href: carriersIndex(),
            },
            {
                title: 'Create',
            },
        ],
    },
});
</script>

<template>
    <Head title="Add a new carrier" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            title="Add a new carrier"
            subtitle="Capture identity, primary branch, and main contact. You can add more branches, contacts, and policies later from the carrier page."
            :divider="false"
        />

        <CarrierForm
            :countries="countries"
            :route="carriersStore.form()"
            submit-label="Create carrier"
        />
    </div>
</template>
