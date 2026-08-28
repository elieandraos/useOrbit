<script setup lang="ts">
import Avatar from '@/components/ui/avatar/Avatar.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { ClientResource } from './client';

defineProps<{
    client: ClientResource;
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Emergency contact</CardTitle>
        </CardHeader>
        <CardContent>
            <div
                v-if="client.emergency_contact_name"
                class="flex items-center gap-3"
            >
                <Avatar :name="client.emergency_contact_name" size="md" />
                <div class="min-w-0 flex-1">
                    <p
                        class="text-[13.5px] leading-none font-semibold text-primary"
                    >
                        {{ client.emergency_contact_name }}
                    </p>
                    <p class="mt-1 text-xs text-secondary">
                        {{ client.emergency_contact_relationship_label }} ·
                        <span class="font-mono">{{
                            client.emergency_contact_phone
                        }}</span>
                    </p>
                </div>
            </div>
            <p v-else class="text-[13px] text-tertiary">
                No emergency contact on file.
            </p>
        </CardContent>
    </Card>
</template>
