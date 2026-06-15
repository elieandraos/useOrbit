<script setup lang="ts">
import { ref } from 'vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import Label from '@/components/ui/label/Label.vue';

const agreed = ref(false);
</script>

<template>
    <Label class="flex items-center gap-2">
        <Checkbox v-model="agreed" />
        <span>I agree to the terms</span>
    </Label>
</template>
