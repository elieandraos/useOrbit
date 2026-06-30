<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardAction, CardContent } from '@/components/ui/card';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import Badge from '@/components/ui/badge/Badge.vue';
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Emergency contact</CardTitle>
            <CardAction>
                <Badge tone="success" dot>On file</Badge>
            </CardAction>
        </CardHeader>
        <CardContent>
            <div class="flex items-center gap-3">
                <Avatar name="David Hartwell" size="md" />
                <div class="flex-1 min-w-0">
                    <p class="text-[13.5px] font-semibold text-primary leading-none">David Hartwell</p>
                    <p class="text-xs text-secondary mt-1">
                        Spouse · <span class="font-mono">(415) 555-0144</span>
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
