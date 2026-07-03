<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { Calendar, Mail, Pencil, Phone, Plus } from '@lucide/vue';
import { computed } from 'vue';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Tab, Tabs } from '@/components/ui/tabs';
import {
    edit as clientsEdit,
    index as clientsIndex,
    show as clientsShow,
} from '@/routes/clients';
import DetailField from './partials/DetailField.vue';

interface ClientResource {
    id: number;
    slug: string;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    full_name: string;
    mothers_name: string | null;
    date_of_birth_formatted: string;
    age: number;
    gender_label: string;
    photo: string | null;
    phone: string;
    email: string | null;
    full_address: string;
    emergency_contact_name: string | null;
    emergency_contact_relationship_label: string | null;
    emergency_contact_phone: string | null;
    enrollment_date_formatted: string;
    lead_source_label: string;
    status: string;
}

const props = defineProps<{
    client: ClientResource;
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Clients',
            href: clientsIndex(),
        },
        {
            title: props.client.full_name,
        },
    ],
});

const policiesCount = 0;

const dateOfBirthLabel = computed(
    () => `${props.client.date_of_birth_formatted} · ${props.client.age} yrs`,
);
</script>

<template>
    <Head :title="client.full_name" />

    <div class="flex flex-1 flex-col">
        <div class="flex items-start gap-4 pb-6">
            <Avatar :name="client.full_name" :size="64" />

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-semibold text-primary">
                        {{ client.full_name }}
                    </h1>
                    <Badge v-if="client.status === 'active'" tone="success" dot
                        >Active client</Badge
                    >
                    <Badge tone="accent">{{ policiesCount }} policies</Badge>
                </div>
                <div
                    class="mt-1.5 flex flex-wrap items-center gap-4 text-[13px] text-secondary"
                >
                    <span class="inline-flex items-center gap-1.5">
                        <Phone class="size-3.5 text-tertiary" />
                        <span class="font-mono">{{ client.phone }}</span>
                    </span>
                    <span
                        v-if="client.email"
                        class="inline-flex items-center gap-1.5"
                    >
                        <Mail class="size-3.5 text-tertiary" />
                        {{ client.email }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <Calendar class="size-3.5 text-tertiary" />
                        Enrolled {{ client.enrollment_date_formatted }}
                    </span>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <Link :href="clientsEdit(client.slug).url">
                    <Button variant="secondary" size="md">
                        <template #leading><Pencil /></template>
                        Edit
                    </Button>
                </Link>
                <Button variant="primary" size="md" disabled>
                    <template #leading><Plus /></template>
                    New policy
                </Button>
            </div>
        </div>

        <Tabs class="mt-6">
            <Tab :href="clientsShow(client.slug)">Overview</Tab>
            <Tab href="#">Policies</Tab>
            <Tab href="#">Documents</Tab>
            <Tab href="#">Notes</Tab>
        </Tabs>

        <div class="grid grid-cols-[360px_1fr] items-start gap-5 pt-6">
            <!-- Left column -->
            <div class="flex flex-col gap-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Personal information</CardTitle>
                        <CardAction>
                            <Link :href="clientsEdit(client.slug).url">
                                <Button variant="ghost" size="sm">
                                    <template #leading
                                        ><Pencil class="size-3.5"
                                    /></template>
                                </Button>
                            </Link>
                        </CardAction>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                            <DetailField
                                label="First name"
                                :value="client.first_name"
                            />
                            <DetailField
                                label="Middle name"
                                :value="client.middle_name"
                            />
                            <DetailField
                                label="Last name"
                                :value="client.last_name"
                            />
                            <DetailField
                                label="Mother's name"
                                :value="client.mothers_name"
                            />
                            <DetailField
                                label="Date of birth"
                                :value="dateOfBirthLabel"
                            />
                            <DetailField
                                label="Gender"
                                :value="client.gender_label"
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Contact</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col gap-3.5">
                            <DetailField
                                label="Phone"
                                :value="client.phone"
                                mono
                            />
                            <DetailField label="Email" :value="client.email" />
                            <DetailField
                                label="Address"
                                :value="client.full_address"
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Enrollment</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                            <DetailField
                                label="Enrolled"
                                :value="client.enrollment_date_formatted"
                            />
                            <DetailField
                                label="Lead source"
                                :value="client.lead_source_label"
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Emergency contact</CardTitle>
                        <CardAction>
                            <Badge
                                :tone="
                                    client.emergency_contact_name
                                        ? 'success'
                                        : 'neutral'
                                "
                                dot
                            >
                                {{
                                    client.emergency_contact_name
                                        ? 'On file'
                                        : 'None'
                                }}
                            </Badge>
                        </CardAction>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="client.emergency_contact_name"
                            class="flex items-center gap-3"
                        >
                            <Avatar
                                :name="client.emergency_contact_name"
                                size="md"
                            />
                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-[13.5px] leading-none font-semibold text-primary"
                                >
                                    {{ client.emergency_contact_name }}
                                </p>
                                <p class="mt-1 text-xs text-secondary">
                                    {{
                                        client.emergency_contact_relationship_label
                                    }}
                                    ·
                                    <span class="font-mono">{{
                                        client.emergency_contact_phone
                                    }}</span>
                                </p>
                            </div>
                        </div>
                        <p v-else class="text-[13px] text-tertiary">
                            No emergency contact on file.
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Right column -->
            <div class="flex flex-col gap-4">
                <Card class="!gap-0 !py-0">
                    <CardHeader class="border-b border-border px-6 py-[14px]">
                        <CardTitle>Policies</CardTitle>
                        <CardAction>
                            <Button variant="ghost" size="sm" disabled>
                                <template #leading><Plus /></template>
                                Add policy
                            </Button>
                        </CardAction>
                    </CardHeader>
                    <CardContent
                        class="!p-6 text-center text-[13px] text-tertiary"
                        >No policies yet.</CardContent
                    >
                </Card>

                <div class="grid grid-cols-2 gap-4">
                    <Card class="!gap-0 !py-0">
                        <CardHeader
                            class="border-b border-border px-6 py-[14px]"
                        >
                            <CardTitle>Quick stats</CardTitle>
                        </CardHeader>
                        <CardContent class="!p-0">
                            <div class="grid grid-cols-2">
                                <div class="px-[18px] py-3.5">
                                    <p
                                        class="font-mono text-[10.5px] font-semibold tracking-[0.06em] text-tertiary uppercase"
                                    >
                                        Annual premium
                                    </p>
                                    <p
                                        class="mt-1 font-mono text-lg font-semibold text-primary"
                                    >
                                        —
                                    </p>
                                </div>
                                <div
                                    class="border-l border-border-subtle px-[18px] py-3.5"
                                >
                                    <p
                                        class="font-mono text-[10.5px] font-semibold tracking-[0.06em] text-tertiary uppercase"
                                    >
                                        Lifetime value
                                    </p>
                                    <p
                                        class="mt-1 font-mono text-lg font-semibold text-primary"
                                    >
                                        —
                                    </p>
                                </div>
                                <div
                                    class="border-t border-border-subtle px-[18px] py-3.5"
                                >
                                    <p
                                        class="font-mono text-[10.5px] font-semibold tracking-[0.06em] text-tertiary uppercase"
                                    >
                                        Years as client
                                    </p>
                                    <p
                                        class="mt-1 font-mono text-lg font-semibold text-primary"
                                    >
                                        —
                                    </p>
                                </div>
                                <div
                                    class="border-t border-l border-border-subtle px-[18px] py-3.5"
                                >
                                    <p
                                        class="font-mono text-[10.5px] font-semibold tracking-[0.06em] text-tertiary uppercase"
                                    >
                                        Renewals due
                                    </p>
                                    <p
                                        class="mt-1 font-mono text-lg font-semibold text-primary"
                                    >
                                        —
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Next renewal</CardTitle>
                        </CardHeader>
                        <CardContent class="text-[13px] text-tertiary"
                            >No upcoming renewals.</CardContent
                        >
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent activities</CardTitle>
                    </CardHeader>
                    <CardContent class="text-[13px] text-tertiary"
                        >No recent activity.</CardContent
                    >
                </Card>
            </div>
        </div>
    </div>
</template>
