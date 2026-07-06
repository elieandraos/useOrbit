<script setup lang="ts">
import { ref } from 'vue';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';

const role = ref('member');
const roles = [
    { label: 'Owner', value: 'owner', desc: 'Full access including member management and billing' },
    { label: 'Member', value: 'member', desc: 'Can view and manage clients but cannot delete or manage members' },
];
</script>

<template>
    <RadioChips v-model="role" :options="roles" direction="vertical" />
</template>
