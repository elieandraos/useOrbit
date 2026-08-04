<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { archive as agentsArchive } from '@/routes/agents';
import type { AgentResource } from './agent';

const agent = defineModel<AgentResource | null>({ default: null });

const isOpen = computed({
    get: () => agent.value !== null,
    set: (value) => {
        if (!value) {
            agent.value = null;
        }
    },
});
</script>

<template>
    <Form
        v-if="agent"
        v-bind="agentsArchive.form(agent.slug)"
        :options="{ preserveScroll: true }"
        v-slot="{ processing }"
        @success="agent = null"
    >
        <Dialog
            v-model:open="isOpen"
            title="Archive this agent?"
            :description="`${agent?.full_name} will be archived and removed from your active agent list. This can be undone later.`"
        >
            <template #footer>
                <Button variant="secondary" @click="agent = null">
                    Cancel
                </Button>
                <Button
                    type="submit"
                    variant="destructive"
                    :disabled="processing"
                >
                    Archive
                </Button>
            </template>
        </Dialog>
    </Form>
</template>
