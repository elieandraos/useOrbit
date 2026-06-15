<script setup lang="ts">
import Label from '@/components/ui/label/Label.vue';
import Input from '@/components/ui/input/Input.vue';
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <Label for="email">Email address</Label>
        <Input id="email" placeholder="you@example.com" />
    </div>
</template>
