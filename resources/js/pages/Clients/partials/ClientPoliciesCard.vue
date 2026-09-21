<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import Button from '@/components/ui/button/Button.vue';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { create as policiesCreate } from '@/routes/policies';
import type { ClientResource } from './client';

const props = defineProps<{
    client: ClientResource;
}>();
</script>

<template>
    <Card>
        <CardHeader bordered>
            <CardTitle>Policies</CardTitle>
            <CardAction>
                <Link
                    :href="
                        policiesCreate.url({
                            query: { client_id: props.client.id },
                        })
                    "
                >
                    <Button variant="ghost" size="sm">
                        <template #leading><Plus /></template>
                        Add policy
                    </Button>
                </Link>
            </CardAction>
        </CardHeader>
        <CardContent class="text-center text-[13px] text-tertiary"
            >No policies yet.</CardContent
        >
    </Card>
</template>
