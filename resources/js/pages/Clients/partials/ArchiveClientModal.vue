<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { destroy as clientsDestroy } from '@/routes/clients';
import type { ClientResource } from './client';

const client = defineModel<ClientResource | null>({ default: null });

const isOpen = computed({
    get: () => client.value !== null,
    set: (value) => {
        if (!value) {
            client.value = null;
        }
    },
});
</script>

<template>
    <Form
        v-if="client"
        v-bind="clientsDestroy.form(client.slug)"
        :options="{ preserveScroll: true }"
        v-slot="{ processing }"
        @success="client = null"
    >
        <Dialog
            v-model:open="isOpen"
            title="Archive this client?"
            :description="`${client?.full_name} will be archived and removed from your active client list. This can be undone later.`"
        >
            <template #footer>
                <Button variant="secondary" @click="client = null">
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
