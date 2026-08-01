<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { archive as carriersArchive } from '@/routes/carriers';
import type { CarrierResource } from './carrier';

const carrier = defineModel<CarrierResource | null>({ default: null });

const isOpen = computed({
    get: () => carrier.value !== null,
    set: (value) => {
        if (!value) {
            carrier.value = null;
        }
    },
});
</script>

<template>
    <Form
        v-if="carrier"
        v-bind="carriersArchive.form(carrier.slug)"
        :options="{ preserveScroll: true }"
        v-slot="{ processing }"
        @success="carrier = null"
    >
        <Dialog
            v-model:open="isOpen"
            title="Archive this carrier?"
            :description="`${carrier?.name} will be archived and removed from your active carrier list. This can be undone later.`"
        >
            <template #footer>
                <Button variant="secondary" @click="carrier = null">
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
