<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Plus, Shield } from '@lucide/vue';
import Button from '@/components/ui/button/Button.vue';
import { create as policiesCreate } from '@/routes/policies';
</script>

<template>
    <div class="flex flex-1 items-center justify-center py-16">
        <div
            class="w-full max-w-[480px] rounded-lg border border-border bg-surface px-9 py-11 text-center shadow-card"
        >
            <div
                class="mx-auto mb-4 flex size-14 items-center justify-center rounded-lg bg-accent-bg text-accent"
            >
                <Shield class="size-6" />
            </div>
            <h2 class="text-lg font-semibold text-primary">No policies yet</h2>
            <p
                class="mx-auto mt-2 max-w-[360px] text-sm leading-relaxed text-secondary"
            >
                Issue your first policy to start tracking effective and expiry
                dates, premiums, discounts, and settlement payments across
                Medical, Automotive, Fire, Life, Expat, and Travel lines.
            </p>
            <div class="mt-6 flex justify-center">
                <Link :href="policiesCreate().url">
                    <Button variant="primary" size="md">
                        <template #leading><Plus /></template>
                        New Policy
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>
