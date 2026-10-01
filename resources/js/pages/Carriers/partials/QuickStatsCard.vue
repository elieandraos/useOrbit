<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { StatCell } from '@/components/ui/stat-cell';

defineProps<{
    policiesCount: number;
}>();
</script>

<template>
    <Card>
        <CardHeader bordered>
            <CardTitle>Quick stats</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
            <StatCell label="Policies" :value="policiesCount" />
        </CardContent>
    </Card>
</template>
