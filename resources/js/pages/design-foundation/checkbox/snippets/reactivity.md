<script setup lang="ts">
import { ref } from 'vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import Label from '@/components/ui/label/Label.vue';

const accepted = ref(false);
</script>

<template>
    <div class="flex flex-col gap-3">
        <Label class="flex items-center gap-2">
            <Checkbox v-model="accepted" />
            <span>I accept the terms and conditions</span>
        </Label>
        <p class="text-sm text-secondary">Accepted: {{ accepted }}</p>
    </div>
</template>
