<script setup lang="ts">
import { ref } from 'vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import { RadioCard } from '@/components/ui/radio-card';

const role = ref('member');
const roles = [
    {
        label: 'Admin',
        value: 'admin',
        desc: 'Full access including member management and billing',
    },
    {
        label: 'Member',
        value: 'member',
        desc: "Can view and manage everything, but can't take destructive actions like archiving or deleting",
    },
];
</script>

<template>
    <FormField label="Role" required>
        <RadioCard v-model="role" name="role" :options="roles" />
    </FormField>
</template>
