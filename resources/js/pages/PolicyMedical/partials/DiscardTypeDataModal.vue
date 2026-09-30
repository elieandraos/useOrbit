<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';

defineProps<{
    title: string;
    description: string;
    discardedItems: string[];
    confirmLabel: string;
}>();

const emit = defineEmits<{
    confirm: [];
}>();

const open = defineModel<boolean>('open', { default: false });
</script>

<template>
    <Dialog v-model:open="open" :title="title" :description="description">
        <ul
            v-if="discardedItems.length > 0"
            class="flex list-disc flex-col gap-1 pl-5 text-sm text-primary"
        >
            <li v-for="(item, index) in discardedItems" :key="index">
                {{ item }}
            </li>
        </ul>

        <template #footer>
            <Button type="button" variant="secondary" @click="open = false">
                Cancel
            </Button>
            <Button
                type="button"
                variant="destructive"
                @click="emit('confirm')"
            >
                {{ confirmLabel }}
            </Button>
        </template>
    </Dialog>
</template>
