<script setup lang="ts">
import Label from '@/components/ui/label/Label.vue';
import Input from '@/components/ui/input/Input.vue';
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <Label for="name" required>Full name</Label>
        <Input id="name" placeholder="John Doe" />
    </div>
</template>
