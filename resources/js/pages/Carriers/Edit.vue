<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import AuditStrip from '@/components/ui/audit-strip/AuditStrip.vue';
import { Avatar } from '@/components/ui/avatar';
import {
    index as carriersIndex,
    show as carriersShow,
    update as carriersUpdate,
} from '@/routes/carriers';
import type { CarrierResource } from './partials/carrier';
import CarrierForm from './partials/CarrierForm.vue';

const props = defineProps<{
    carrier: CarrierResource;
    countries: { id: number; name: string }[];
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Carriers',
            href: carriersIndex(),
        },
        {
            title: props.carrier.name,
            href: carriersShow({ carrier: props.carrier.slug }),
        },
        {
            title: 'Edit',
        },
    ],
});
</script>

<template>
    <Head :title="`Edit ${carrier.name}`" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            :title="`Edit ${carrier.name}`"
            subtitle="Update identity, headquarters, and primary contact details."
            :divider="false"
        >
            <template #avatar>
                <Avatar :name="carrier.name" :size="48" />
            </template>
        </PageHeader>

        <div class="mx-auto mb-4 w-full max-w-[1100px]">
            <AuditStrip
                :created="carrier.created_at"
                :updated="carrier.updated_at"
                :by="carrier.updated_by_name ?? '—'"
            />
        </div>

        <CarrierForm
            :carrier="carrier"
            :countries="countries"
            :route="carriersUpdate.form({ carrier: carrier.slug })"
            submit-label="Save changes"
        />
    </div>
</template>
