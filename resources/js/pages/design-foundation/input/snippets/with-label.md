<script setup lang="ts">
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <Label for="name">Full name</Label>
        <Input id="name" placeholder="John Doe" />
    </div>
</template>
