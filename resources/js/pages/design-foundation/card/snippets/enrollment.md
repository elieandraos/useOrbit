<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Enrollment</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <div class="flex flex-col gap-1">
                    <p class="text-[10.5px] font-mono font-semibold text-tertiary uppercase tracking-[0.06em]">Enrolled</p>
                    <p class="text-[13.5px] text-primary">Feb 8, 2024</p>
                </div>
                <div class="flex flex-col gap-1">
                    <p class="text-[10.5px] font-mono font-semibold text-tertiary uppercase tracking-[0.06em]">Lead source</p>
                    <p class="text-[13.5px] text-primary">Referral</p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
