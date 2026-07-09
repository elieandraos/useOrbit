<script setup lang="ts">
import { ref, useId } from 'vue';
import Switch from '@/components/ui/switch/Switch.vue';

const enabled = ref(false);
const twoFactorId = useId();
</script>

<template>
    <div class="flex flex-col gap-3">
        <div class="flex items-center gap-2">
            <Switch :id="twoFactorId" v-model="enabled" />
            <label :for="twoFactorId" class="cursor-pointer text-sm text-primary"
                >Two-factor authentication</label
            >
        </div>
        <p class="text-sm text-secondary">Enabled: {{ enabled }}</p>
    </div>
</template>
