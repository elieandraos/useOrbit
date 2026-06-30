<script setup lang="ts">
import FormField from '@/components/ui/form-field/FormField.vue';
import Input from '@/components/ui/input/Input.vue';
</script>

<template>
    <FormField label="Email address" for="email">
        <Input id="email" placeholder="you@example.com" />
    </FormField>
</template>