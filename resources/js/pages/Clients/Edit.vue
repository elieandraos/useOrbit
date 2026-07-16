<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import AuditStrip from '@/components/ui/audit-strip/AuditStrip.vue';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import { index as clientsIndex, show as clientsShow, update as clientsUpdate } from '@/routes/clients';
import ClientForm from './partials/ClientForm.vue';

interface ClientResource {
    id: number;
    slug: string;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    mothers_name: string | null;
    date_of_birth: string;
    gender: string;
    photo: string | null;
    phone: string;
    email: string | null;
    street: string | null;
    building_floor: string | null;
    state_id: number | null;
    city: string | null;
    country_id: number | null;
    country_name: string | null;
    state_name: string | null;
    emergency_contact_name: string | null;
    emergency_contact_relationship: string | null;
    emergency_contact_phone: string | null;
    enrollment_date: string;
    lead_source: string;
    status: string;
    created_by: number;
    updated_by: number | null;
    created_at: string;
    updated_at: string;
    updated_by_name: string | null;
}

const props = defineProps<{
    client: ClientResource;
    countries: { id: number; name: string }[];
    genders: { label: string; value: string }[];
    leadSources: { label: string; value: string }[];
    emergencyContactRelationships: { label: string; value: string }[];
}>();

const fullName = `${props.client.first_name} ${props.client.last_name}`;

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Clients',
            href: clientsIndex(),
        },
        {
            title: fullName,
            href: clientsShow({ client: props.client.slug }),
        },
        {
            title: 'Edit',
        },
    ],
});
</script>

<template>
    <Head :title="`Edit ${fullName}`" />

    <div class="flex flex-1 flex-col">
        <PageHeader :title="`Edit ${fullName}`" subtitle="Update personal details, contact info, and emergency contact. Linked policies stay attached." :divider="false">
            <template #avatar>
                <Avatar :name="fullName" :size="48" />
            </template>
        </PageHeader>

        <div class="mx-auto mb-4 w-full max-w-[1100px]">
            <AuditStrip :created="client.created_at" :updated="client.updated_at" :by="client.updated_by_name ?? '—'" />
        </div>

        <ClientForm
            :client="client"
            :countries="countries"
            :genders="genders"
            :lead-sources="leadSources"
            :emergency-contact-relationships="emergencyContactRelationships"
            :route="clientsUpdate.form({ client: client.slug })"
            submit-label="Save changes"
        />
    </div>
</template>
