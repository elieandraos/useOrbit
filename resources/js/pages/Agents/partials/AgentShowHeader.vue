<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Calendar, Download, Mail, Pencil, Phone } from '@lucide/vue';
import { computed } from 'vue';
import { Avatar } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { Spinner } from '@/components/ui/spinner';
import { useFileExport } from '@/composables/useFileExport';
import {
    edit as agentsEdit,
    exportPdf as agentsExportPdf,
} from '@/routes/agents';
import type { AgentResource } from './agent';

const props = defineProps<{
    agent: AgentResource;
    clientsCount: number;
    policiesCount: number;
}>();

const { isExporting, exportFile } = useFileExport();

const exportUrl = computed(() => agentsExportPdf(props.agent.slug).url);

function exportAgent(): Promise<void> {
    return exportFile(exportUrl.value, `${props.agent.slug}.pdf`, {
        success: 'Agent exported.',
        error: 'Failed to export agent. Please try again.',
    });
}
</script>

<template>
    <div class="pb-0 sm:pb-6">
        <!-- Mobile: centered hero, bled edge-to-edge to match AppContent's mobile padding -->
        <div
            class="-mx-4 flex flex-col items-center border-b border-border-subtle bg-surface px-4 py-6 sm:hidden"
        >
            <Avatar :name="agent.full_name" :size="72" />

            <h1 class="mt-3 text-lg font-semibold text-primary">
                {{ agent.full_name }}
            </h1>

            <div
                class="mt-1.5 flex flex-wrap items-center justify-center gap-2"
            >
                <Badge v-if="agent.status === 'active'" tone="success" dot
                    >Active</Badge
                >
                <Badge v-else tone="warning">Archived</Badge>
                <Badge tone="accent">{{ clientsCount }} clients</Badge>
                <Badge tone="neutral">{{ policiesCount }} policies</Badge>
            </div>

            <div class="mt-5 flex items-center gap-2">
                <Button
                    variant="secondary"
                    size="sm"
                    :disabled="isExporting"
                    @click="exportAgent"
                >
                    <template #leading>
                        <Spinner v-if="isExporting" />
                        <Download v-else />
                    </template>
                    Export
                </Button>
                <Link :href="agentsEdit(agent.slug).url">
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
                <Avatar :name="agent.full_name" :size="64" />

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-semibold text-primary">
                            {{ agent.full_name }}
                        </h1>
                        <Badge
                            v-if="agent.status === 'active'"
                            tone="success"
                            dot
                            >Active</Badge
                        >
                        <Badge v-else tone="warning">Archived</Badge>
                        <Badge tone="accent">{{ clientsCount }} clients</Badge>
                        <Badge tone="neutral"
                            >{{ policiesCount }} policies</Badge
                        >
                    </div>
                    <div
                        class="mt-1.5 flex flex-wrap items-center gap-4 text-[13px] text-secondary"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <Phone class="size-3.5 text-tertiary" />
                            <span class="font-mono">{{ agent.phone }}</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <Mail class="size-3.5 text-tertiary" />
                            {{ agent.email }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <Calendar class="size-3.5 text-tertiary" />
                            Joined {{ agent.joined_at_formatted }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <Button
                    variant="secondary"
                    size="md"
                    :disabled="isExporting"
                    @click="exportAgent"
                >
                    <template #leading>
                        <Spinner v-if="isExporting" />
                        <Download v-else />
                    </template>
                    Export
                </Button>
                <Link :href="agentsEdit(agent.slug).url">
                    <Button variant="secondary" size="md">
                        <template #leading><Pencil /></template>
                        Edit
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>
