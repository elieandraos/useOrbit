<script setup lang="ts">
import Select from '@/components/ui/select/Select.vue';
import Label from '@/components/ui/label/Label.vue';
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <Label for="fruit">Fruit</Label>
        <Select id="fruit" placeholder="Pick a fruit">
            <option value="apple">Apple</option>
            <option value="banana">Banana</option>
            <option value="cherry">Cherry</option>
        </Select>
    </div>
</template>
