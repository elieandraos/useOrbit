<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import PageHeader from '@/components/shell/PageHeader.vue';
import AuditStrip from '@/components/ui/audit-strip/AuditStrip.vue';
import { Avatar } from '@/components/ui/avatar';
import {
    index as agentsIndex,
    show as agentsShow,
    update as agentsUpdate,
} from '@/routes/agents';
import type { AgentResource } from './partials/agent';
import AgentForm from './partials/AgentForm.vue';

const props = defineProps<{
    agent: AgentResource;
    countries: { id: number; name: string }[];
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Agents',
            href: agentsIndex(),
        },
        {
            title: props.agent.full_name,
            href: agentsShow({ agent: props.agent.slug }),
        },
        {
            title: 'Edit',
        },
    ],
});
</script>

<template>
    <Head :title="`Edit ${agent.full_name}`" />

    <div class="flex flex-1 flex-col">
        <PageHeader
            :title="`Edit ${agent.full_name}`"
            subtitle="Update the agent's personal information, contact details, and address."
            :divider="false"
        >
            <template #avatar>
                <Avatar :name="agent.full_name" :size="48" />
            </template>
        </PageHeader>

        <div class="mx-auto mb-4 w-full max-w-[1100px]">
            <AuditStrip
                :created="agent.created_at"
                :updated="agent.updated_at"
                :by="agent.updated_by_name ?? '—'"
            />
        </div>

        <AgentForm
            :agent="agent"
            :countries="countries"
            :route="agentsUpdate.form({ agent: agent.slug })"
            submit-label="Save changes"
        />
    </div>
</template>
