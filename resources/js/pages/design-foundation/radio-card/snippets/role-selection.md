<script setup lang="ts">
import { ref } from 'vue';
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
    <RadioCard v-model="role" :options="roles" />
</template>
