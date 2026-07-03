<script setup lang="ts">
import { Plus } from '@lucide/vue';
import Button from '@/components/ui/button/Button.vue';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
</script>

<template>
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
        <CardContent class="!p-6 text-center text-[13px] text-tertiary"
            >No policies yet.</CardContent
        >
    </Card>
</template>
