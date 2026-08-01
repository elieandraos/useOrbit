<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { edit as carriersEdit } from '@/routes/carriers';
import type { CarrierResource } from './carrier';
import DetailField from './DetailField.vue';

defineProps<{
    carrier: CarrierResource;
}>();
</script>

<template>
    <Card>
        <CardHeader bordered>
            <CardTitle>Carrier information</CardTitle>
            <CardAction>
                <Link :href="carriersEdit(carrier.slug).url">
                    <button
                        type="button"
                        title="Edit"
                        class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md text-secondary transition-colors hover:bg-sunken"
                    >
                        <Pencil class="size-3.5" />
                    </button>
                </Link>
            </CardAction>
        </CardHeader>
        <CardContent>
            <div class="flex flex-col gap-3.5">
                <DetailField
                    label="Headquarters"
                    :value="carrier.branch?.city"
                />
                <DetailField label="Branches" value="1" mono />
                <DetailField
                    label="Onboarded"
                    :value="carrier.onboarded_date_formatted"
                />
            </div>
        </CardContent>
    </Card>
</template>
