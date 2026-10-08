<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { StatCell } from '@/components/ui/stat-cell';

defineProps<{
    policiesCount: number;
    clientsCount: number;
}>();
</script>

<template>
    <Card>
        <CardContent class="p-0">
            <div class="grid grid-cols-2 divide-x divide-border-subtle">
                <StatCell label="Policies" :value="policiesCount" />
                <StatCell label="Clients" :value="clientsCount" />
            </div>
        </CardContent>
    </Card>
</template>
