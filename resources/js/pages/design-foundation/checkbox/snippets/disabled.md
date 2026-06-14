<script setup lang="ts">
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import Label from '@/components/ui/label/Label.vue';
</script>

<template>
    <div class="flex flex-col gap-3">
        <Label class="flex items-center gap-2">
            <Checkbox :model-value="false" disabled />
            <span>Disabled unchecked</span>
        </Label>
        <Label class="flex items-center gap-2">
            <Checkbox :model-value="true" disabled />
            <span>Disabled checked</span>
        </Label>
    </div>
</template>
