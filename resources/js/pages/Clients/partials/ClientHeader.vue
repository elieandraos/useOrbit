<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Calendar, Mail, Pencil, Phone, Plus } from '@lucide/vue';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { edit as clientsEdit } from '@/routes/clients';
import type { ClientResource } from './client';

defineProps<{
    client: ClientResource;
    policiesCount: number;
}>();
</script>

<template>
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
</template>
