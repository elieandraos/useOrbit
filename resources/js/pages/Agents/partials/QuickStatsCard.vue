<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { AgentResource } from './agent';

defineProps<{
    agent: AgentResource;
}>();
</script>

<template>
    <Card>
        <CardHeader bordered>
            <CardTitle>Quick stats</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
            <div class="grid grid-cols-2">
                <div class="px-[18px] py-3.5">
                    <p
                        class="font-mono text-[10.5px] tracking-[0.06em] text-tertiary uppercase"
                    >
                        Clients
                    </p>
                    <p
                        class="mt-1 font-mono text-lg font-semibold text-primary"
                    >
                        0
                    </p>
                </div>
                <div class="border-l border-border-subtle px-[18px] py-3.5">
                    <p
                        class="font-mono text-[10.5px] tracking-[0.06em] text-tertiary uppercase"
                    >
                        Policies
                    </p>
                    <p
                        class="mt-1 font-mono text-lg font-semibold text-primary"
                    >
                        0
                    </p>
                </div>
                <div class="border-t border-border-subtle px-[18px] py-3.5">
                    <p
                        class="font-mono text-[10.5px] tracking-[0.06em] text-tertiary uppercase"
                    >
                        Book premium
                    </p>
                    <p
                        class="mt-1 font-mono text-lg font-semibold text-primary"
                    >
                        $0
                    </p>
                </div>
                <div
                    class="border-t border-l border-border-subtle px-[18px] py-3.5"
                >
                    <p
                        class="font-mono text-[10.5px] tracking-[0.06em] text-tertiary uppercase"
                    >
                        Tenure
                    </p>
                    <p
                        class="mt-1 font-mono text-lg font-semibold text-primary"
                    >
                        {{ agent.tenure }}
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
