<script setup lang="ts">
import { ref } from 'vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import Label from '@/components/ui/label/Label.vue';

const notifications = ref({ email: true, sms: false, push: true });
</script>

<template>
    <div class="flex flex-col gap-3">
        <Label class="flex items-center gap-2">
            <Checkbox v-model="notifications.email" />
            <span>Email notifications</span>
        </Label>
        <Label class="flex items-center gap-2">
            <Checkbox v-model="notifications.sms" />
            <span>SMS notifications</span>
        </Label>
        <Label class="flex items-center gap-2">
            <Checkbox v-model="notifications.push" />
            <span>Push notifications</span>
        </Label>
    </div>
</template>
