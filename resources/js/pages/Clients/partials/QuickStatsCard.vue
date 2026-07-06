<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
                        Annual premium
                    </p>
                    <p
                        class="mt-1 font-mono text-lg font-semibold text-primary"
                    >
                        —
                    </p>
                </div>
                <div class="border-l border-border-subtle px-[18px] py-3.5">
                    <p
                        class="font-mono text-[10.5px] tracking-[0.06em] text-tertiary uppercase"
                    >
                        Lifetime value
                    </p>
                    <p
                        class="mt-1 font-mono text-lg font-semibold text-primary"
                    >
                        —
                    </p>
                </div>
                <div class="border-t border-border-subtle px-[18px] py-3.5">
                    <p
                        class="font-mono text-[10.5px] tracking-[0.06em] text-tertiary uppercase"
                    >
                        Years as client
                    </p>
                    <p
                        class="mt-1 font-mono text-lg font-semibold text-primary"
                    >
                        —
                    </p>
                </div>
                <div
                    class="border-t border-l border-border-subtle px-[18px] py-3.5"
                >
                    <p
                        class="font-mono text-[10.5px] tracking-[0.06em] text-tertiary uppercase"
                    >
                        Renewals due
                    </p>
                    <p
                        class="mt-1 font-mono text-lg font-semibold text-primary"
                    >
                        —
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
