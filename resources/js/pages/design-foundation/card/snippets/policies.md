<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardAction, CardContent } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { Shield, ChevronRight, Plus } from '@lucide/vue';

const policies = [
    { id: 'POL-2042', line: 'Medicare Advantage', carrier: 'Humana', premium: '$184.20 / mo', status: 'Active' },
    { id: 'POL-2043', line: 'Auto · Full coverage', carrier: 'Progressive', premium: '$1,840 / yr', status: 'Active' },
    { id: 'POL-2044', line: 'Homeowners', carrier: 'Travelers', premium: '$1,205 / yr', status: 'Renewing' },
];

function statusTone(status: string) {
    if (status === 'Active') return 'success';
    if (status === 'Renewing') return 'warning';
    return 'neutral';
}
</script>

<template>
    <Card class="!py-0 !gap-0">
        <CardHeader class="border-b border-border px-6 py-[14px]">
            <CardTitle>Policies</CardTitle>
            <CardAction>
                <Button variant="ghost" size="sm">
                    <template #leading><Plus /></template>
                    Add policy
                </Button>
            </CardAction>
        </CardHeader>
        <CardContent class="!p-0">
            <div
                v-for="policy in policies"
                :key="policy.id"
                class="flex items-center gap-3.5 px-6 py-3 border-b border-border-subtle last:border-b-0 hover:bg-sunken transition-colors cursor-pointer"
            >
                <div class="flex size-8 items-center justify-center rounded-[10px] bg-accent-bg text-accent shrink-0">
                    <Shield class="size-4" />
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-[13.5px] font-semibold text-primary">{{ policy.line }}</span>
                        <Badge :tone="statusTone(policy.status)" dot>{{ policy.status }}</Badge>
                    </div>
                    <p class="text-[11.5px] font-mono text-tertiary mt-0.5">{{ policy.id }} · {{ policy.carrier }}</p>
                </div>
                <p class="text-[13px] font-mono font-medium text-primary shrink-0">{{ policy.premium }}</p>
                <ChevronRight class="size-4 text-tertiary shrink-0" />
            </div>
        </CardContent>
    </Card>
</template>
