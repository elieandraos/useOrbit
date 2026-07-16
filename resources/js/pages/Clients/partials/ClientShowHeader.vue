<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Calendar, Download, Mail, Pencil, Phone, Plus } from '@lucide/vue';
import { computed } from 'vue';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { Spinner } from '@/components/ui/spinner';
import { useFileExport } from '@/composables/useFileExport';
import {
    edit as clientsEdit,
    exportPdf as clientsExportPdf,
} from '@/routes/clients';
import type { ClientResource } from './client';

const props = defineProps<{
    client: ClientResource;
    policiesCount: number;
}>();

const { isExporting, exportFile } = useFileExport();

const exportUrl = computed(() => clientsExportPdf(props.client.slug).url);

function exportClient(): Promise<void> {
    return exportFile(exportUrl.value, `${props.client.slug}.pdf`, {
        success: 'Client exported.',
        error: 'Failed to export client. Please try again.',
    });
}
</script>

<template>
    <div class="pb-0 sm:pb-6">
        <!-- Mobile: centered hero, bled edge-to-edge to match AppContent's mobile padding -->
        <div
            class="-mx-4 flex flex-col items-center border-b border-border-subtle bg-surface px-4 py-6 sm:hidden"
        >
            <Avatar :name="client.full_name" :size="72" />

            <h1 class="mt-3 text-lg font-semibold text-primary">
                {{ client.full_name }}
            </h1>

            <div class="mt-1.5 flex flex-wrap items-center justify-center gap-2">
                <Badge v-if="client.status === 'active'" tone="success" dot
                    >Active client</Badge
                >
                <Badge v-else-if="client.status === 'archived'" tone="warning"
                    >Archived</Badge
                >
                <Badge tone="accent">{{ policiesCount }} policies</Badge>
            </div>

            <div class="mt-5 flex items-center gap-2">
                <Button
                    variant="secondary"
                    size="sm"
                    :disabled="isExporting"
                    @click="exportClient"
                >
                    <template #leading>
                        <Spinner v-if="isExporting" />
                        <Download v-else />
                    </template>
                    Export
                </Button>
                <Link :href="clientsEdit(client.slug).url">
                    <Button variant="secondary" size="sm">
                        <template #leading><Pencil /></template>
                        Edit
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Desktop (`sm` and above): original left-aligned layout -->
        <div class="hidden items-start justify-between gap-4 sm:flex">
            <div class="flex items-start gap-4">
                <Avatar :name="client.full_name" :size="64" />

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-semibold text-primary">
                            {{ client.full_name }}
                        </h1>
                        <Badge
                            v-if="client.status === 'active'"
                            tone="success"
                            dot
                            >Active client</Badge
                        >
                        <Badge
                            v-else-if="client.status === 'archived'"
                            tone="warning"
                            >Archived</Badge
                        >
                        <Badge tone="accent"
                            >{{ policiesCount }} policies</Badge
                        >
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
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <Button
                    variant="secondary"
                    size="md"
                    :disabled="isExporting"
                    @click="exportClient"
                >
                    <template #leading>
                        <Spinner v-if="isExporting" />
                        <Download v-else />
                    </template>
                    Export
                </Button>
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
    </div>
</template>
