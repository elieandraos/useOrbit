<script setup lang="ts">
import { ref } from 'vue';
import Switch from '@/components/ui/switch/Switch.vue';
import SwitchField from '@/components/ui/switch/SwitchField.vue';

const emailNotifications = ref(true);
const autoRenew = ref(false);
</script>

<template>
    <div class="flex flex-col gap-2.5">
        <SwitchField
            label="Email notifications"
            description="Get a digest every Monday morning."
            v-slot="{ id }"
        >
            <Switch :id="id" v-model="emailNotifications" />
        </SwitchField>
        <SwitchField
            label="Auto-renew policies"
            description="Trigger renewal flow 30 days before expiry."
            v-slot="{ id }"
        >
            <Switch :id="id" v-model="autoRenew" />
        </SwitchField>
    </div>
</template>
