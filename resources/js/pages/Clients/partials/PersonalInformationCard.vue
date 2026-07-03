<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import { computed } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { edit as clientsEdit } from '@/routes/clients';
import type { ClientResource } from './client';
import DetailField from './DetailField.vue';

const props = defineProps<{
    client: ClientResource;
}>();

const dateOfBirthLabel = computed(
    () => `${props.client.date_of_birth_formatted} · ${props.client.age} yrs`,
);
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Personal information</CardTitle>
            <CardAction>
                <Link :href="clientsEdit(client.slug).url">
                    <Button variant="ghost" size="sm">
                        <template #leading
                            ><Pencil class="size-3.5"
                        /></template>
                    </Button>
                </Link>
            </CardAction>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <DetailField label="First name" :value="client.first_name" />
                <DetailField label="Middle name" :value="client.middle_name" />
                <DetailField label="Last name" :value="client.last_name" />
                <DetailField
                    label="Mother's name"
                    :value="client.mothers_name"
                />
                <DetailField label="Date of birth" :value="dateOfBirthLabel" />
                <DetailField label="Gender" :value="client.gender_label" />
            </div>
        </CardContent>
    </Card>
</template>
