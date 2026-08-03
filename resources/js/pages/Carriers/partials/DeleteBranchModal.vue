<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { destroy as carriersBranchesDestroy } from '@/routes/carriers/branches';
import type { CarrierBranchResource } from './carrier';

const branchToDelete = defineModel<CarrierBranchResource | null>({
    default: null,
});

const isOpen = computed({
    get: () => branchToDelete.value !== null,
    set: (value) => {
        if (!value) {
            branchToDelete.value = null;
        }
    },
});
</script>

<template>
    <Form
        v-if="branchToDelete"
        v-bind="carriersBranchesDestroy.form(branchToDelete.id)"
        :options="{ preserveScroll: true }"
        v-slot="{ processing }"
        @success="branchToDelete = null"
    >
        <Dialog
            v-model:open="isOpen"
            title="Delete this branch?"
            description="This branch and its contact details will be permanently removed. This can't be undone."
        >
            <template #footer>
                <Button variant="secondary" @click="branchToDelete = null">
                    Cancel
                </Button>
                <Button
                    type="submit"
                    variant="destructive"
                    :disabled="processing"
                >
                    Delete branch
                </Button>
            </template>
        </Dialog>
    </Form>
</template>
