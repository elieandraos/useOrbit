<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { policyStatusTone } from '@/lib/policyStatusTone';
import { edit as policiesMedicalEdit } from '@/routes/policies/medical';
import type { PolicyResource } from '@/types/policy';

defineProps<{
    policy: PolicyResource;
}>();
</script>

<template>
    <div
        class="flex flex-col items-start justify-between gap-4 pb-0 sm:flex-row sm:items-center sm:pb-6"
    >
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-xl font-semibold text-primary sm:text-2xl">
                    {{ policy.policy_number }}
                </h1>
                <Badge :tone="policyStatusTone[policy.status] ?? 'neutral'" dot>
                    {{ policy.status_label }}
                </Badge>
                <Badge tone="accent">{{ policy.type_label }}</Badge>
            </div>
            <div
                class="mt-1.5 flex flex-wrap items-center gap-4 text-[13px] text-secondary"
            >
                <span>{{ policy.class_label }} · {{ policy.subclass }}</span>
                <span>{{ policy.client.full_name }}</span>
                <span>{{ policy.carrier.name }}</span>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <Link :href="policiesMedicalEdit(policy.slug).url">
                <Button variant="secondary" size="md">
                    <template #leading><Pencil /></template>
                    Edit
                </Button>
            </Link>
        </div>
    </div>
</template>
